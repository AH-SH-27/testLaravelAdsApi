<?php

namespace App\Console\Commands;

use App\Http\Requests\StoreAdRequest;
use Illuminate\Console\Command;

class TestValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:validation {category_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test dynamic validation rules for a category';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categoryId = $this->argument('category_id');

        $request = new StoreAdRequest();
        $request->merge(['category_id' => $categoryId]);

        $rules = $request->rules();

        $this->info("Validation rules for category ID: {$categoryId}");
        $this->table(['Field', 'Rules'], collect($rules)->map(fn($r, $k) => [$k, implode('|', $r)]));
    }
}
