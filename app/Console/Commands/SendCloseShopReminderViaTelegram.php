<?php

namespace App\Console\Commands;

use App\Notification\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendCloseShopReminderViaTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:close-shop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send A Close Shop Reminder through Telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        logger('sending close shop reminder via tg');

        $users = DB::table('shifts')
            ->select('users.first_name', 'users.id')
            ->where('date', now()->format('Y-m-d'))
            ->whereRaw('HOUR(shifts.to) = 20')
            ->leftJoin('staff', 'staff.id', '=', 'shifts.staff_id')
            ->leftJoin('users', 'staff.user_id', '=', 'users.id')
            ->get();

        if($users->isEmpty()){
            logger('no shifts today');
            return;
        }

        $tg = resolve(Telegram::class);
        $tg->sentShopCloseReminder(
            $name = implode(',', $users->pluck('first_name')->toArray())
        );

        logger('done..  ' . $name);
    }
}
