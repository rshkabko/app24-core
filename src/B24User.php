<?php
/**
 * Это класс для работы с данными КОНКРЕТНОГО юзера
 * Т.е. есть данные авторизации портала, т.к. это не то же самое!
 */

namespace Flamix\App24Core;

use App\Exceptions\App24Exception;
use Flamix\App24Core\Controllers\UserController;
use Bitrix24\Bitrix24;

class B24User
{
    protected static B24User $instances;
    protected static Bitrix24 $obB24User;
    protected static int $expire = 0;

    /**
     * Singleton.
     *
     * @return B24User
     * @throws App24Exception
     */
    public static function getInstance(): B24User
    {
        if (empty(self::$instances)) {
            self::$instances = new static;
        }

        if (empty(self::$obB24User)) {
            self::$obB24User = self::getAuth();
        }

        return self::$instances;
    }

    /**
     * Возвращает конектор портала
     * Возвращает ссылку-инстантс на коннектор Битрикс24 Rest API для дальнейшей работы.
     *
     * @return Bitrix24
     */
    public static function getConnect(): Bitrix24
    {
        return self::$obB24User;
    }

    /**
     * Шлем без проверок токена и прочего сразу на авторизацию к Битриксу
     * @return Bitrix24
     */
    private static function getAuth(): Bitrix24
    {
        $session = isset(request()->DOMAIN) && isset(request()->member_id) ? self::setSessions() : self::getSessions();
        throw_unless(isset($session), new App24Exception(trans('app24::error.b24user_open_portal_in_b24'), 444));
        // Secure issue
        if (isset(request()->DOMAIN) && !isset(request()->member_id) && request()->DOMAIN !== $session['domain']) {
            throw new App24Exception('Domain not same! Please, reload page in Bitrix24!', 447);
        }

        $obB24User = self::getB24Auth($session);
        if (self::isAuthExpire()) {
            $obB24User->setRedirectUri('https://' . request()->DOMAIN . '/rest/');
            $new_session = $obB24User->getNewAccessToken();

            //TODO: Костыль! Ставим наш домен из сессии. Дело в том, что getNewAccessToken() возвращает 'oauth.bitrix.info'
            // как домен, поэтому далее запросы идут туда, а так быть не может
            if ($new_session['domain'] !== $session['domain']) {
                $new_session['domain'] = $session['domain'];
            }

            self::setSessions($new_session);
            return self::getB24Auth($new_session);
        }

        return $obB24User;
    }

    /**
     * Получаем авторизацию по переданным данным сессии пользователя
     *
     * @param array $session
     * @return Bitrix24
     * @throws App24Exception
     * @throws \Bitrix24\Exceptions\Bitrix24Exception
     */
    private static function getB24Auth(array $session): Bitrix24
    {
        $obB24App = new Bitrix24(false);
        $portal_scope = config('app24.access.scope');

        throw_unless($portal_scope, new App24Exception(trans('app24::error.security_cant_find_portal_code', ['domain' => $session['domain'], 'code' => config('app.name')])));

        $scope = explode(',', $portal_scope);
        $scope = !is_array($scope) ? [$portal_scope] : $scope; // Always array

        $obB24App->setApplicationScope($scope);
        $obB24App->setApplicationId(config('app24.access.id'));
        $obB24App->setApplicationSecret(config('app24.access.secret'));

        $obB24App->setDomain($session['domain']);
        $obB24App->setMemberId($session['member_id']);
        $obB24App->setAccessToken($session['access_token']);
        $obB24App->setRefreshToken($session['refresh_token']);

        return $obB24App;
    }

    /**
     * Устанавливаем сессию пользователя
     *
     * @param array $session
     * @return array
     * @throws App24Exception
     */
    private static function setSessions(array $session = []): array
    {
        $request = request()->all();
        if (empty($session) && (!isset($request['DOMAIN']) || !isset($request['member_id'])))
            throw new App24Exception(trans('app24::error.b24user_open_portal_in_b24'), 444);

        if (empty($session)) {
            $session = [
                'domain' => $request['DOMAIN'],
                'member_id' => $request['member_id'],
                'access_token' => $request['AUTH_ID'],
                'refresh_token' => $request['REFRESH_ID'],
                'expires' => time() + (int)$request['AUTH_EXPIRES'],
            ];
        }

        self::$expire = $session['expires'] ?? 0;
        session($session);
        return $session;
    }

    /**
     * Возвращаем текущую сессию пользователя
     *
     * @return array
     * @throws App24Exception
     */
    private static function getSessions(): array
    {
        $session = request()->session()->all();
        if (!isset($session['domain']) || !isset($session['member_id']) || !isset($session['access_token']) || !isset($session['refresh_token']))
            throw new App24Exception(trans('app24::error.b24user_cant_take_session'));

        return $session;
    }

    /**
     * А не прострочилась ли в юзера регистрация
     *
     * @return bool
     */
    public static function isAuthExpire(): bool
    {
        return time() >= self::$expire;
    }

    /**
     * Является ли текущий пользователь админом?
     * !!Заглушка!!
     * TODO: Удалить
     * @return mixed
     */
    public static function isAdmin(): bool
    {
        dd('B24User::isAdmin() - DEPRECATED!');
        return UserController::isAdmin();
    }

    /**
     * Получаем ID текущего пользователя
     * !!Заглушка!!
     * TODO: Удалить
     * @return int
     */
    public static function getID(): int
    {
        dd('B24User::getID() - DEPRECATED!');
        return UserController::getID();
    }
}