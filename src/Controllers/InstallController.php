<?php

namespace Flamix\App24Core\Controllers;

use Exception;
use Bitrix24\Event\Event;
use Flamix\App24Core\Language;
use Flamix\App24Core\App24;
use App\Exceptions\App24Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class InstallController extends Controller
{
    /**
     * Install app.
     *
     * @param Request $request
     * @return mixed
     */
    public function install(Request $request): View|array
    {
        info('Install portal', $request->all());

        // Set lang
        $lang = Language::filter($request->get('LANG', config('app24.app.def_lang')));
        app()->setLocale($lang);

        // When add new app to vendor
        if (empty($request['member_id'] ?? null)) {
            return ['status' => false, 'error' => 'Auth is empty!'];
        }

        // Disable cache for install
        config(['app24.cache.domain' => 0]);

        // Verify request before install
        $portal = (object)AuthController::getAuthFields($request->all());
        $portal_auth = AuthController::getPortalAuthWithoutCheck($portal);
        $user_id = $portal_auth->call('user.current')['result']['ID'] ?? null;
        throw_unless($user_id, App24Exception::class, 'Wrong access! Bad user_id!');
        $app_info = $portal_auth->call('app.info')['result'] ?? null;
        $region = preg_replace('/_.*/', '', $app_info['LICENSE'] ?? 'not_found');
        throw_if(empty($region), App24Exception::class, 'Empty region!');
        // TODO: Fix this when app will send "server_domain"
        $oauth_server = $region === 'ru' ? 'oauth.bitrix.tech' : config('app24.access.oauth_server', 'oauth.bitrix.info');

        $portal->user_id = $user_id;
        $portal->oauth_server = $oauth_server;
        $portal->region = $region;

        // Install to DB
        $id = app(AuthController::class)->insertOrUpdateOAuth((array)$portal);
        throw_unless($id, App24Exception::class, trans('app24::error.portal_cant_install'));

        // Language to DB
        Language::setPortalLanguage($id, $lang);

        // Add event to uninstall
        $this->uninstallEvent($id);

        // Fire event
        event('onAfterPortalInstall', [$id]);

        // Return view or redirect (here you can put your own logic)
        return app(config('app24.app.views'))->install($id);
    }

    /**
     * Uninstall app.
     * TODO: Add SALT and check!
     *
     * @param Request $request
     * @param string $hash
     * @return array
     * @throws App24Exception
     */
    public function destroy(Request $request, string $hash): array
    {
        info('Delete portal', $request->all());
        $domain = $request->auth['domain'] ?? '';
        $need_clear_data = boolval($request->data['CLEAN'] ?? false);
        if (empty($domain)) {
            return ['status' => false, 'error' => 'Domain is empty'];
        }

        $portal_id = PortalController::getId($domain);
        if ($hash !== $this->getHash($portal_id)) {
            return ['status' => false, 'error' => 'Bad hash'];
        }

        event('onBeforePortalDestroy', [$portal_id]);

        return ['status' => app(config('app24.app.views'))->uninstall($portal_id, $need_clear_data)];
    }

    /**
     * Add event to uninstall.
     * We need to delete portal from our DB (optional).
     *
     * @param int $portal_id
     * @return bool
     */
    private function uninstallEvent(int $portal_id): bool
    {
        try {
            $events = new Event(App24::getInstance($portal_id)->getConnect());
            $result = $events->bind('ONAPPUNINSTALL', route('app24.uninstall', ['hash' => $this->getHash($portal_id)]));
        } catch (Exception $exception) {
            info('Error unregister event', [$exception->getMessage()]);
        }

        return boolval($result['result'] ?? false);
    }

    /**
     * Generate uniq hash for uninstall.
     * Unique for every portal.
     *
     * @param int $portal_id
     * @return string
     */
    private function getHash(int $portal_id): string
    {
        return hash_hmac('sha256', "delete_{$portal_id}", config('app.key'));
    }
}