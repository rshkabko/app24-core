<?php

namespace App\Exceptions;

use Exception;
use Flamix\App24Core\Controllers\PortalController;
use Flamix\App24Core\Models\Portals;
use Flamix\App24Core\Controllers\App\SecurityController;

class FxException extends Exception
{
    private string $msg;

    public function report()
    {
    }

    /**
     * Если есть налияия миделваре API - значит мы возвращаем ошибки в JSON
     * Если нет, значи как HTML
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        $route = $request->route();
        $actions = ($route) ? $route->getAction() : false;
        $middleware = $actions['middleware'] ?? [];

        if (!empty($actions) && (in_array('api', $middleware) || in_array('json', $middleware))) {
            return response()->json(['status' => 'error', 'msg' => $this->getMessage()]);
        } else if ($route && $this->getCode() == 333) {
            return redirect()->route('license');
        }
        
        $this->setMsg($this->getMessage());
        return response()->view(
            'b24app.errors',
            [
                'server' => $this->getServer(),
                'title' => $this->getTitle(),
                'text' => $this->msg,
                'buttons' => $this->getButton($this->getCode()),
                'error' => true,
            ],
            ($this->getCode() > 0 ? $this->getCode() : 200)
        );
    }

    private function setMsg(string $msg)
    {
        $this->msg = trim($msg);
    }

    /**
     * А нужно ли сервер отображать?
     *
     * @return string
     */
    private function getServer()
    {
        if (str_contains($this->msg, '[SERVER::WORK]')) {
            $this->setMsg(str_replace('[SERVER::WORK]', '', $this->msg));
            return 'work';
        }

        if (str_contains($this->msg, '[SERVER::WARNING]')) {
            $this->setMsg(str_replace('[SERVER::WARNING]', '', $this->msg));
            return 'warning';
        }

        if (str_contains($this->msg, '[SERVER::ERROR]')) {
            $this->setMsg(str_replace('[SERVER::ERROR]', '', $this->msg));
            return 'error';
        }

        return false;
    }

    /**
     * Парсим тайтл по "!"
     *
     * @return string
     */
    private function getTitle(): string
    {
        if (str_contains($this->msg, '!')) {
            $msg = explode('!', $this->msg);
            $title = $msg['0'];
            unset($msg['0']);
            $msg = implode('! <br/>', $msg);
            $this->setMsg($msg);
            return $title;
        }

        return trans('flamix::msg.error');
    }

    /**
     * Получаем ссылки для ошибки
     *
     * @param int $code
     * @return array
     */
    private function getButton(int $code = 0): array
    {
        return match ($code) {
            402 => ['text' => '☢️ DELETE ALL ☢️', 'link' => '/b24app/uninstall/duplicate/' . Portals::getByDomain(PortalController::getDomain(), false)->id . '/' . SecurityController::getToken(Portals::getByDomain(PortalController::getDomain(), false)->id) . '?DOMAIN=' . PortalController::getDomain()],
            404 => ['text' => 'Contact us', 'link' => 'https://flamix.solutions/about/contacts.php'],
            444 => ['text' => trans('flamix::msg.reload'), 'link' => 'javascript:BX24.reloadWindow();'],
            500 => ['text' => 'Contact us', 'link' => 'https://flamix.solutions/about/contacts.php'],
            default => ['text' => trans('flamix::msg.repeat'), 'link' => '/']
        };
    }
}
