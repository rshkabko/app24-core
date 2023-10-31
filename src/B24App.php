<?php

namespace Flamix\App24Core;

use App\Exceptions\FxException;
use Flamix\App24Core\Models\Portals;
use Flamix\App24Core\Controllers\AuthController;
use Flamix\App24Core\Controllers\PortalController;
use Bitrix24\Bitrix24;
use Bitrix24\App\App as Bitrix24App;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class B24App
{
    private static $instances;
    private static $obB24App;
    private static $portalData;
    private static int $portalId = 0;

    public static function getInstance(int $id = 0): B24App
    {
        // При руной инициализации не нужно записывать домен в сессию
        if (!$id)
            AuthController::setDomainToSession();

        // Если мы пытаемся вызвать разные порталы (те разные входящие ID), то нам нужно сбрасывать авторизации
        if ($id > 0 && $id !== self::getId())
            self::reInstance();

        if (empty(self::$instances))
            self::$instances = new static;

        if ($id)
            self::setID($id);

        if (empty(self::$portalData))
            self::$portalData = PortalController::get(self::$portalId);

        if (empty(self::$obB24App))
            self::$obB24App = self::connectPortal();

        // Переопределяем ID с БД
        self::setID(self::$portalData->id ?? 0);

        return self::$instances;
    }

    /**
     * Сброс инстанса для разных авторизаций
     *
     * Например для работы со всема порталами во время крона
     */
    public static function reInstance()
    {
        self::$instances = false;
        self::$obB24App = false;
        self::$portalData = false;
        self::$portalId = 0;
    }

    /**
     * Устанавливаем ID портала (по умолчанию пытаемся вычеслить самостоятельно)
     *
     * @param int $id
     * @return mixed
     */
    public static function setID(int $id)
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
    public static function getConnect()
    {
        // Проверка, а был ли вызван getInstance() перед getConnect()
        // Если не вызван, тогда у нас будут проблемы с обновлением токена, потому что он обновляется в getInstance()
        // Обычно он вызывается в Middelware или через B24App::getInstance(), но если забыть будет ГГ
        if (!self::getID())
            throw new FxException('getConnect(): You mast call B24App::getInstance() or add B24App Middelware!');

        return self::$obB24App;
    }

    public static function getPortalData()
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
     * @throws FxException
     */
    private static function connectPortal()
    {
        $obB24App = AuthController::getPortalAuthWithoutCheck(self::$portalData);

        // Если токен просрочился (проверяем через нашу БД, т.к. это БЫСТРЕЕ)
        // То мы сперва получаем новые доступы, апдейтим все в БД и дальше заново инициализируем класс для подключения
        // Если осталось 5 минут (300 сек) - мы все равно обновляем
        if (Carbon::parse(self::$portalData->expires)?->subSeconds(300) <= Carbon::now())
            $obB24App = self::forceUpdateAndConnectPortal($obB24App);

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
        $obB24App->setRedirectUri('https://' . self::$portalData->domain . '/rest/');
        $access = $obB24App->getNewAccessToken();

        $access['scope'] = self::$portalData->scope;
        $access['domain'] = self::$portalData->domain;

        // Update auth data
        app(AuthController::class)->insertOrUpdateOAuth($access);
        // Set new auth data
        self::$portalData = Portals::getData(self::$portalId);
        return AuthController::getPortalAuthWithoutCheck(self::$portalData);
    }

    /**
     * Получаем инофрмацию о приложении
     *
     * @param int $portal_id
     * @return array
     * @throws FxException
     */
    public static function getAppInfo(int $portal_id = 0): array
    {
        return (new Bitrix24App(self::getInstance($portal_id)->getConnect()))->info();
    }

    /**
     * Получаем тип приложение
     * Example pro, demo, standart, etc
     *
     * @param int $portal_id
     * @return bool
     */
    public static function getAppLicenseType(int $portal_id = 0): string|bool
    {
        $app = self::getAppInfo($portal_id);

        if (!empty($app['result']['LICENSE'])) {
            $app = explode('_', $app['result']['LICENSE']);
            return $app['1'];
        }

        return false;
    }

    /**
     * Возвращаем какие типы лицензий поддерживает приложение
     *
     * @return array|bool
     */
    public static function getAccessedLicenseType(): bool|array
    {
        $type = config('app24.access.access_type');
        if ($type)
            return explode(',', $type);

        return false;
    }

    /**
     * Какие тарифы запрещены
     *
     * @return array|bool
     */
    public static function getDeniedLicenseType(): bool|array
    {
        $type = config('app24.access.access_type');
        if ($type)
            return explode(',', $type);

        return false;
    }
}
