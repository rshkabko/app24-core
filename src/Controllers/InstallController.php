<?php

namespace Flamix\App24Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\FxException;
use Flamix\App24Core\Actions\SendLeadToOurBitrix24;

class InstallController extends Controller
{
    /**
     * Установка приложения
     *
     * @param Request $request
     * @return string
     */
    public function installing(Request $request)
    {
        if ($request)
            Log::debug($request);

        // Ставим язык для вьюхи при установке
        $lang = $request->get('LANG', config('b24app.app.def_lang'));

        \App::setLocale($lang);

        //Записываем авторизацию в БД
        $id = app(AuthController::class)->insertOrUpdateOAuth($request['auth']);
        if (!$id)
            throw new FxException(trans('flamix::msg.portal_cant_install'));

        //Сохраняем язык в БД
        \Flamix\App24Core\Language::setPortalLanguage($id, $lang);

        //Генерируем секретный токен
        \Flamix\App24Core\Controllers\App\SecurityController::insert($id, config('b24app.access.secret_force_update'));

        //Записываем пользователя при установке
        $user_id = Bitrix24\UserController::updateOrCreateMainUserPortal($id);

        //Регистрируем событие на удаление
        EventsController::registerEventUninstall($id);

        //Добавляем ЛИД в наш портал
        SendLeadToOurBitrix24::handle();

        //Шлем уведомлению установщику
        app(Bitrix24\NotificationsController::class)->setUser($user_id)->sendWhenInstall();

        //Events
        event('onAfterPortalInstall', [$id]);

        // Если приложение платное - передаем цены
        if (!LicenseController::isFreeApp()) {
            $price = LicenseController::getProductPrices(config('b24app.license.application_product_id'));
            $price['monthly'] = $price['PRICE']['1'] ?? 0;
            $price['annyally'] = 12 * $price['PRICE']['12'] ?? 0;
        } else
            $price = [];

        return view('b24app.license.install', $price);
    }

    /**
     * Срабатывает при удалении портала
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Log::debug('Delete portal', $request->all());
        $domain = $request->auth['domain'];
        $need_clear_data = (bool)$request->data['CLEAN'];

        if (!empty($domain)) {
            $portal_id = PortalController::getId($domain);
            PortalController::destroy($portal_id, $need_clear_data);
        }
    }
}
