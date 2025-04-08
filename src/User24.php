<?php

namespace Flamix\App24Core;

use App\Exceptions\App24Exception;
use Bitrix24\Bitrix24;

/**
 * This class is for working with the data of a SPECIFIC user.
 */
class User24
{
    protected static User24 $instances;
    protected static Bitrix24 $obUser24;
    protected static int $expire = 0;

    /**
     * Singleton.
     *
     * @return User24
     * @throws App24Exception
     */
    public static function getInstance(): User24
    {
        if (empty(self::$instances)) {
            self::$instances = new static;
        }

        if (empty(self::$obUser24)) {
            self::$obUser24 = self::getAuth();
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
        return self::$obUser24;
    }

    /**
     * Шлем без проверок токена и прочего сразу на авторизацию к Битриксу
     * @return Bitrix24
     */
    private static function getAuth(): Bitrix24
    {
        $session = isset(request()->DOMAIN) && isset(request()->member_id) ? self::setSessions() : self::getSessions();
        throw_unless(isset($session), new App24Exception(trans('app24::error.user24_open_portal_in_b24'), 444));
        // Secure issue
        if (isset(request()->DOMAIN) && !isset(request()->member_id) && request()->DOMAIN !== $session['domain']) {
            throw new App24Exception('Domain not same! Please, reload page in Bitrix24!', 447);
        }

        $obUser24 = self::getB24Auth($session);
        if (self::isAuthExpire()) {
            $obUser24->setRedirectUri('https://' . request()->DOMAIN . '/rest/');
            $new_session = $obUser24->getNewAccessToken();

            // TODO: Костыль! Ставим наш домен из сессии. Дело в том, что getNewAccessToken() возвращает 'oauth.bitrix.info'
            // как домен, поэтому далее запросы идут туда, а так быть не может
            if ($new_session['domain'] !== $session['domain']) {
                $new_session['domain'] = $session['domain'];
            }

            self::setSessions($new_session);
            return self::getB24Auth($new_session);
        }

        return $obUser24;
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
        $obApp24 = new Bitrix24();

        $obApp24->setApplicationId(config('app24.access.id'));
        $obApp24->setApplicationSecret(config('app24.access.secret'));

        $obApp24->setDomain($session['domain']);
        $obApp24->setMemberId($session['member_id']);
        $obApp24->setAccessToken($session['access_token']);
        $obApp24->setRefreshToken($session['refresh_token']);

        return $obApp24;
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
        if (empty($session) && (!isset($request['DOMAIN']) || !isset($request['member_id']))) {
            throw new App24Exception(trans('app24::error.user24_open_portal_in_b24'), 444);
        }

        if (empty($session)) {
            $session = [
                'domain' => $request['DOMAIN'],
                'member_id' => $request['member_id'],
                'access_token' => $request['AUTH_ID'],
                'refresh_token' => $request['REFRESH_ID'],
                'expires' => time() + intval($request['AUTH_EXPIRES']),
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
        if (!isset($session['domain']) || !isset($session['member_id']) || !isset($session['access_token']) || !isset($session['refresh_token'])) {
            throw new App24Exception(trans('app24::error.user24_cant_take_session'));
        }

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
}