<?php

namespace Flamix\App24Core\Controllers;

use Exception;
use Bitrix24\Event\Event;
use Flamix\App24Core\Language;
use Flamix\App24Core\B24App;
use App\Exceptions\FxException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InstallController extends Controller
{
    /**
     * Install app.
     *
     * @param Request $request
     * @return mixed
     */
    public function install(Request $request)
    {
        info('Install portal', $request->all());

        // Set lang to view
        $lang = Language::filter($request->get('LANG'));
        app()->setLocale($lang);

        // Install to DB
        $id = app(AuthController::class)->insertOrUpdateOAuth($request['auth']);
        throw_unless($id, FxException::class, trans('flamix::error.portal_cant_install'));

        // Add event to uninstall
        $this->uninstallEvent($id);

        // Fire event
        event('onAfterPortalInstall', [$id]);

        // TODO: Add listener to my SDK
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
     * @throws FxException
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

        return ['status' => PortalController::destroy($portal_id, $need_clear_data)];
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
            $events = new Event(B24App::getInstance($portal_id)->getConnect());
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
