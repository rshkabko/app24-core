<?php

namespace Flamix\App24Core;

use App\Exceptions\App24Exception;
use Flamix\App24Core\Models\Portals;
use Flamix\App24Core\Controllers\AuthController;
use Flamix\App24Core\Controllers\PortalController;
use Bitrix24\Bitrix24;
use Bitrix24\App\App as Bitrix24App;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class B24App
{
    protected static ?B24App $instances;
    protected static ?Bitrix24 $obB24App;
    protected static ?Portals $portalData;
    protected static int $portalId = 0;

    /**
     * Singelton.
     *
     * @param int $id
     * @return B24App
     * @throws App24Exception
     */
    public static function getInstance(int $id = 0): B24App
    {
        // При руной инициализации не нужно записывать домен в сессию
        if (!$id) {
            AuthController::setDomainToSession();
        }

        // Если мы пытаемся вызвать разные порталы (те разные входящие ID), то нам нужно сбрасывать авторизации
        if ($id > 0 && $id !== self::getId()) {
            self::reInstance();
        }

        if (empty(self::$instances)) {
            self::$instances = new static;
        }

        if ($id) {
            self::setID($id);
        }

        if (empty(self::$portalData)) {
            self::$portalData = PortalController::get(self::$portalId);
        }

        if (empty(self::$obB24App)) {
            self::$obB24App = self::connectPortal();
        }

        // Переопределяем ID с БД
        self::setID(self::$portalData->id ?? 0);
        return self::$instances;
    }

    /**
     * ReInstance singelton.
     *
     * For example, for working with all portals during cron.
     * In this case, we need to reset the authorization.
     *
     * @return void
     */
    public static function reInstance(): void
    {
        self::$instances = null;
        self::$obB24App = null;
        self::$portalData = null;
        self::$portalId = 0;
    }

    /**
     * Устанавливаем ID портала (по умолчанию пытаемся вычеслить самостоятельно)
     *
     * @param int $id
     * @return B24App
     */
    public static function setID(int $id): B24App
    {
        self::$portalId = $id;
        return self::$instances;
    }

    /**
     * Получаем ID портала в нашей системе
     *
     * @return int
     */
    public static function getID(): int
    {
        return self::$portalId;
    }

    /**
     * Возвращает конектор портала
     *
     * Возвращает ссылку-инстантс на коннектор Битрикс24 Rest API для дальнейшей работы.
     *
     * @return mixed
     */
    public static function getConnect(): Bitrix24
    {
        // Проверка, а был ли вызван getInstance() перед getConnect()
        // Если не вызван, тогда у нас будут проблемы с обновлением токена, потому что он обновляется в getInstance()
        // Обычно он вызывается в Middelware или через B24App::getInstance(), но если забыть будет ГГ
        throw_unless(self::getID(), App24Exception::class, 'getConnect(): You mast call B24App::getInstance() or add B24App Middelware!');
        return self::$obB24App;
    }

    /**
     * Get Portal Data Model.
     *
     * @return Portals
     */
    public static function getPortalData(): Portals
    {
        return self::$portalData;
    }

    /**
     * Внутренний метод, который проверяет еще и время жизни токена (1 час)
     *
     * Проверка делается по внутренниму сохраненному значению времени. Если проверка покажет что
     * токен просрочен, метод самостоятельно его обновит и перепишет все переменные синглтона.
     *
     * @return Bitrix24
     * @throws App24Exception
     */
    private static function connectPortal(): Bitrix24
    {
        $obB24App = AuthController::getPortalAuthWithoutCheck(self::$portalData);

        // Если токен просрочился (проверяем через нашу БД, т.к. это БЫСТРЕЕ)
        // То мы сперва получаем новые доступы, апдейтим все в БД и дальше заново инициализируем класс для подключения
        // Если осталось 5 минут (300 сек) - мы все равно обновляем
        if (Carbon::parse(self::getPortalData()->expires)?->subSeconds(300) <= Carbon::now()) {
            $obB24App = self::forceUpdateAndConnectPortal($obB24App);
        }

        return $obB24App;
    }

    /**
     * Принудительное обновления токена
     *
     * @param Bitrix24 $obB24App
     * @return Bitrix24
     */
    public static function forceUpdateAndConnectPortal(Bitrix24 $obB24App): Bitrix24
    {
        $obB24App->setRedirectUri("https://" . self::getPortalData()->domain . "/rest/");
        $access = $obB24App->getNewAccessToken();

        $access['scope'] = self::getPortalData()->scope;
        $access['domain'] = self::getPortalData()->domain;

        // Update auth data
        app(AuthController::class)->insertOrUpdateOAuth($access);
        // Set new auth data
        self::$portalData = Portals::getData(self::$portalId);
        return AuthController::getPortalAuthWithoutCheck(self::getPortalData());
    }

    /**
     * Получаем инофрмацию о приложении
     *
     * @param int $portal_id
     * @return array
     * @throws App24Exception
     */
    public static function getAppInfo(int $portal_id = 0): array
    {
        return (new Bitrix24App(self::getInstance($portal_id)->getConnect()))->info();
    }
}