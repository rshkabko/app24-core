<?php

namespace Flamix\App24Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Exceptions\FxException;
use Illuminate\Support\Facades\DB;
use Setting;

class SettingController extends Controller
{
    private static string $table = 'settings';

    /**
     * Когда нельзя автоматически считать домен портала, необходимо ставить вручную
     * Example: When use Jobs
     *
     * @param int $portal_id
     * @return $this
     */
    public function setPortal(int $portal_id): SettingController
    {
        Setting::setExtraColumns(['portal_id' => $portal_id]);
        return $this;
    }

    /**
     * Save setting to DB.
     *
     * @param string|array $key
     * @param mixed $value
     */
    public static function save(string|array $key, mixed $value = false): void
    {
        if (is_array($key)) {
            setting($key)->save();
        } else {
            setting([$key => $value])->save();
        }
    }

    /**
     * Delete setting by key or keys.
     *
     * @param string|array $keys
     * @return void
     */
    public static function forget(string|array $keys): void
    {
        if (is_array($keys)) {
            foreach ($keys as $key) {
                Setting::forget($key);
            }
        } else {
            Setting::forget($keys);
        }

        setting()->save();
    }

    /**
     * Сохраняем параметры настроек
     *
     * @param Request $request
     * @return array
     * @todo Сделать фильтрацию лишних настроек
     */
    public function saveSettings(Request $request)
    {
        $settings = $request->all();
        event('onBeforeSaveSettings', [&$settings]);

        foreach ($settings as $key => $value) {
            if ($key == 'DOMAIN' || $key == 'api_token')
                continue;

            $key = self::prepereKeyToSave($key);
            if (is_array($value))
                self::doSavedString($value, $key);
            else
                Setting::set($key, filter_var($value, FILTER_SANITIZE_STRING));
        }

        LogController::portal(trans('flamix::error.log_setting_saving'), $settings);
        // Final action - https://github.com/anlutro/laravel-settings/blob/master/src/SaveMiddleware.php
        setting()->save();

        return [
            'data' => $request->all(),
            'status' => 'success',
            'msg' => trans('flamix::error.setting_saved'),
        ];
    }

    /**
     * Внутренний метод для чистки входящих масивов
     *
     * @param string $key
     * @return string
     */
    private static function prepereKeyToSave(string $key)
    {
        return mb_strtoupper(str_replace('.', '_', $key));
    }

    /**
     * Рекурсивно сохраняет строку для сохранения ее как настройки
     *
     * @param array $array
     * @param $prev_key
     * @return mixed
     */
    private static function doSavedString(array $array, $prev_key)
    {
        foreach ($array as $key => $value) {
            $key = self::prepereKeyToSave($key);
            if (!is_array($value))
                Setting::set($prev_key . '.' . $key, filter_var($value, FILTER_SANITIZE_STRING));
            else
                self::doSavedString($value, $prev_key . '.' . $key);
        }
    }

    /**
     * Получаем все параметры из настроек
     *
     * @return mixed
     */
    public static function getAllSettings()
    {
        $settings = Setting::all();
        event('onAfterGetAllSettings', [&$settings]);
        return $settings;
    }


    /**
     * Получаем один параметр из настройки
     *
     * @param string $name Название настройки ( также можно передать как ?NAME=XXXX )
     * @param string $default Что возвращаем по дефолту
     * @return array
     * @throws FxException
     */
    public static function getSetting(string $name = '', $default = false)
    {
        if (empty($name)) {
            $request = request();

            if (empty($request->get('NAME')))
                throw new FxException('Send NAME parameters or in functions!');

            $name = $request->get('NAME');
        }

        $setting_value = setting($name, $default);

        if ($setting_value == 'true')
            return true;

        if ($setting_value == 'false')
            return false;

        return $setting_value;
    }

    /**
     * Удаление настроек
     *
     * @param Request $request
     * @return array
     * @throws FxException
     */
    public static function deleteSettings(Request $request): array
    {
        $key = $request->input('key');
        if (empty($key)) throw new FxException('Empty key');

        self::forget($key);

        event('onAfterDeleteSettings', [$key]);

        return [
            'key' => $key,
            'status' => 'success',
            'message' => '',
            'msg' => 'Deleted!'
        ];
    }

    /**
     * Жестко записываем настройки
     *
     * @param string $key
     * @param $value
     * @param array $options
     * @return int
     */
    public static function hardInsertOrUpdate(string $key, $value, array $options)
    {
        $insert = array_merge(['key' => $key, 'value' => $value], $options);
        return DB::table(self::$table)->updateOrInsert($insert, $options);
    }

    /**
     * Возвращаем PORTAL_ID по ключу настроек
     *
     * @param string $key
     * @return int
     * @throws \Exception
     */
    public static function getPortalIdBySettingKey(string $key): int
    {
        $portal_id = DB::table(self::$table)
            ->where('key', 'like', '%' . $key . '%')
            ->whereRaw('portal_id <> ""')
            ->limit(1)
            ->value('portal_id');

        if ($portal_id > 0)
            return $portal_id;

        throw new FxException('Cant find Portal by setting key: ' . $key);
    }

    /**
     * Remove all portal settings
     *
     * @param int $portal_id
     */
    public static function deletePortalSettings(int $portal_id)
    {
        DB::table(self::$table)->where('portal_id', $portal_id)->delete();
    }
}
