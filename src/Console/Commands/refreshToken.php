<?php

namespace Flamix\App24Core\Console\Commands;

use Bitrix24\User\User;
use Flamix\App24Core\Models\Portals;
use Illuminate\Console\Command;
use Flamix\App24Core\App24;

/**
 * php artisan app24:refresh-token --dev=1000
 */
class refreshToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app24:refresh-token {--dev=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh token to portal';

    public function handle()
    {
        $dev_portal_id = $this->option('dev');
        $result = ['SUCCESS' => [], 'ERROR' => []];
        $success = $error = 0;
        $portals = Portals::select(['id', 'user_id', 'app_code', 'domain', 'expires', 'updated_at']);

        if ($dev_portal_id > 0) {
            $portals->where('id', $dev_portal_id);
        } else {
            $portals->where('expires', '<=', now()->subDays(2))->where('expires', '>=', now()->subDays(20));
        }

        $portals = $portals->get();
        $this->log('--- START --- Portal count: ' . count($portals));

        foreach ($portals as $portal) {
            if ($portal->id) {
                $this->log("Refresh token on portal #{$portal->id} ({$portal->domain})!");
                try {
                    $obUser24 = new User(App24::getInstance($portal->id)->getConnect());
                    $obUser24->getById($portal->user_id);

                    $this->log("Success: last_expires - {$portal->expires} and last_updated_at: {$portal->expires}");
                    $result['SUCCESS'][] = $portal->toArray();
                    ++$success;
                } catch (\Exception $e) {
                    ++$error;
                    $result['ERROR'][] = array_merge($portal->toArray(), ['msg' => $e->getMessage()]);
                    $this->log('Error. Message: ' . $e->getMessage());
                }
            }
        }

        // Showing info
        $this->table(['id', 'user_id', 'app_code', 'expires', 'updated_at'], $result['ERROR'],);
        $this->table(['id', 'user_id', 'app_code', 'expires', 'updated_at', 'Message'], $result['SUCCESS']);
        $this->log('--- END ---', ['success' => $success, 'error' => $error]);
    }

    private function log(string $msg, array $data = [])
    {
        dump($msg, $data);
    }
}