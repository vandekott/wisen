<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Telegram\Word;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        try {
            DB::beginTransaction();
            User::updateOrCreate([
                'email' => 'admin@wisen.io',
            ] ,[
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('$BY5h#Hjp25dJu!9'), // $BY5h#Hjp25dJu!9
            ]);

            $sample10k = collect(explode(PHP_EOL, file_get_contents(database_path('seeders/sample_words.txt'))));

            //dd(count($sample10k));
            Word::withoutEvents(function () use ($sample10k) {
                $sample10k->unique()->each(function ($word) {
                    Word::updateOrCreate([
                        'word' => $word,
                    ], [
                        'word' => $word,
                        'score' => random_int(1, 3),
                    ]);
                });
            });
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
