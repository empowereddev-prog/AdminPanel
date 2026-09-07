<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mood; 
use Carbon\Carbon;
use App\Models\ChildMood;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NegativeMoodAlert;
class CheckNegativeMoods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-negative-moods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $negativeMoodNames = Mood::where('type', 'negative')->pluck('name')->toArray();
        // dd($negativeMoodNames); // customize as needed
        $negativeMoodIds = Mood::whereIn('name', $negativeMoodNames)->pluck('id');

        $fromDate = Carbon::now()->subDays(5);

        $childMoodEntries = ChildMood::whereIn('mood_id', $negativeMoodIds)
            ->where('created_at', '>=', $fromDate)
            ->with('child') // assumes a relation to child (user)
            ->get()
            ->groupBy('child_id');
// dd($childMoodEntries);
        foreach ($childMoodEntries as $childId => $moods) {
            $child = $moods->first()->child;

            // Notify child (if applicable)
            if ($child) {
                Notification::route('mail', $child->email)
                    ->notify(new NegativeMoodAlert($child, $moods));
            }

            // Notify parent if parent_id exists
            if ($child && $child->parent_id) {
                $parent = User::find($child->parent_id);
                if ($parent) {
                    Notification::route('mail', $parent->email)
                        ->notify(new NegativeMoodAlert($child, $moods, true));
                }
            }
        }
// dd($child);
        $this->info('Negative mood check complete.');
    }
}
