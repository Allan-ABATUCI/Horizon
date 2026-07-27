<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réinitialise les données (migrate:fresh --seed) — actif uniquement si DEMO_MODE=true, pour une instance publique de démonstration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('app.demo_mode')) {
            $this->error("DEMO_MODE n'est pas activé — commande ignorée par sécurité.");

            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Données de démo réinitialisées.');

        return self::SUCCESS;
    }
}
