<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user balances and send notifications if to low';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        echo "Checking users balances...\n";
        User::checkBalances();
        echo "Checking users balances done\n";

        return 0;
    }
}
