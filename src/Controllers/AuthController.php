<?php

namespace Flamix\App24Core\Controllers;

use App\Exceptions\FxException;
use Exception;
use Bitrix24\Bitrix24;
use Flamix\App24Core\B24App;
use Flamix\App24Core\Controllers\App\CacheController;
use Flamix\App24Core\Models\Portals;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Обновляем данные авторизации
     *
     * @param array|bool|null $data
     * @return int
     * @throws FxException
     */
    public function insertOrUpdateOAuth(array|bool|null $data = false): int
    {
        throw_if(empty(config('app.name')), FxException::class, trans('flamix::error.portal_env__app_name_bugs'));

        $insert = self::getAuthFields($data);
        $search = ['domain' => $insert['domain']];

        // Так надо для уже существующих доменов
        // Потому что создает всегда без _pay при запросе на домен
        try {
            $portal_id = (B24App::getID()) ?: Portals::getId($insert['domain']);
        } catch (Exception $e) {
            $portal_id = 0;
        }

        if ($portal_id) {
            // Действующий портал
            $search['id'] = $portal_id;

            // Обновляем только авторизационные ключи, иначе перепишет не по _pay
            $insert = [
                'member_id' => $insert['member_id'],
                'access_token' => $insert['access_token'],
                'refresh_token' => $insert['refresh_token'],
                'scope' => $insert['scope'],
                'expires' => $insert['expires'],
            ];
        } else {
            // New portal
            $search['app_code'] = $insert['app_code'];
            $insert['admin_only'] = config('app24.access.admin_only'); // Only admin?
        }

        $id = intval(app(Portals::class)->updateOrCreate($search, $insert)->id ?? 0);
        throw_unless($id, FxException::class, trans('flamix::error.portal_cant_create_update'));

        unset($insert, $data, $portal_id, $search);
        CacheController::clearPortalCache($id);
        return $id;
    }

    /**
     * Какие данные передали - такие мы и ставим в конфиг Bitrix24,
     * чтобы он делал с ними запросы
     *
     * Мы ничео не вилидруем, просто передали и впере
     *
     * @param object $portalData
     * @return Bitrix24
     * @throws FxException
     * @throws \Bitrix24\Exceptions\Bitrix24Exception
     */
    public static function getPortalAuthWithoutCheck(object $portalData): Bitrix24
    {
        $obB24App = new Bitrix24(false);
        $scope = self::prepareScope($portalData->scope);
        $app_id = !empty($portalData->app_id) ? $portalData->app_id : config('app24.access.id');
        $app_secret = !empty($portalData->app_secret) ? $portalData->app_secret : config('app24.access.secret');

        if (!$app_id || !$app_secret)
            throw new FxException('Bad APP_ID or APP_SECRET!');

        $obB24App->setApplicationScope($scope);
        $obB24App->setApplicationId($app_id);
        $obB24App->setApplicationSecret($app_secret);

        $obB24App->setDomain($portalData->domain);
        $obB24App->setMemberId($portalData->member_id);
        $obB24App->setAccessToken($portalData->access_token);
        $obB24App->setRefreshToken($portalData->refresh_token);

        return $obB24App;
    }

    /**
     * Битрикс24 может присылать данные авторизации в разных местах
     * Именно в этом файле мы смотрим где они хранятся и работаем с ними
     *
     * @return array|Request
     * @throws FxException
     */
    public static function getAuthArray(): array|Request
    {
        $request = request();

        if ($request->has('auth'))
            $data = $request->input('auth');
        else if ($request->has('DOMAIN'))
            $data = $request;
        else if (session()->has('DOMAIN'))
            $data = ['DOMAIN' => session('DOMAIN')];
        else
            throw new FxException(trans('flamix::error.portal_empty_domain_in_get'));

        return $data;
    }

    /**
     * Собираем данные для авторизации
     * Проблема в том, что они прилетают все время разные, а тут мы их проверяем и нарезаем
     */
    public static function getAuthFields($force_data = false): array
    {
        // Если мы не передали $force_data, то мы то же самое берем с request()
        $data = ($force_data) ?: self::getAuthArray();

        $auth = [
            'app_code' => config('app.name'),
            'app_id' => config('app24.access.id'),
            'app_secret' => config('app24.access.secret'),

            'domain' => ($force_data && $data['domain']) ? $data['domain'] : self::getDomain(),

            'scope' => $data['scope'] ?? false,
            'member_id' => $data['member_id'] ?? false,
            'access_token' => $data['access_token'] ?? $data['AUTH_ID'] ?? false,
            'refresh_token' => $data['refresh_token'] ?? $data['REFRESH_ID'] ?? false,
            'user_id' => 0,
        ];

        //expires
        if (!empty($data['expires'] ?? ''))
            $auth['expires'] = Carbon::createFromTimestamp($data['expires']);
        else if (!empty($data['AUTH_ID'] ?? ''))
            $auth['expires'] = Carbon::now()->addSeconds(3500);

        return $auth;
    }

    /**
     * Ставим текущий домен в сессию
     *
     * @return void
     * @throws FxException
     */
    public static function setDomainToSession(): void
    {
        $domain = self::getDomain();
        if ($domain) {
            session(['DOMAIN' => $domain]);
        }
    }

    /**
     * Чтобы поставить скоупы через setApplicationScope($scope), нужно чтобы они всегда были массивом
     *
     * @param string $scope
     * @return array
     */
    private static function prepareScope(string $scope): array
    {
        $scope = explode(',', $scope);
        return is_array($scope) ? $scope : [$scope];
    }

    /**
     * Получаем домен с текущей авторизации
     * Просто и домен может по разному писаться (так есть в Битрикс24)
     *
     * @return string
     * @throws FxException
     */
    public static function getDomain(): string
    {
        // TODO: Add sdd and remove
        return PortalController::getDomain();
    }
}
