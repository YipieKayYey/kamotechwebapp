<?php

namespace App\Console\Commands;

use App\Http\Controllers\PromotionController;
use Illuminate\Console\Command;

class CreateSlidePromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:create-slides 
                            {--force : Force creation even if promotions exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample promotions using all 4 public slide images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating promotions with slide images...');

        $promotionController = new PromotionController;

        try {
            $promotionController->createSamplePromotions();

            $this->info('✅ Successfully created/updated promotions using all 4 slide images:');
            $this->line('   • /images/slide/1.jpg - PRICE STARTS AT 450 PESOS!');
            $this->line('   • /images/slide/2.jpg - FREE SURVEY & FREE CHECKUP!');
            $this->line('   • /images/slide/3.jpg - EXPERT TECHNICIANS');
            $this->line('   • /images/slide/4.jpg - 24/7 EMERGENCY SERVICE');

            $this->newLine();
            $this->info('Your hero slider will now cycle through all 4 images!');
            $this->info('You can manage these promotions in the admin panel at /admin/promotions');

        } catch (\Exception $e) {
            $this->error('Failed to create promotions: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
