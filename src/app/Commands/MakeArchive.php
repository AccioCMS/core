<?php

namespace Accio\App\Commands;

use App\Models\PostType;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeArchive extends Command{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:archive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create archive tables';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(){
        $this->call("config:cache");

        // Check if archive is enabled
        if(env('DB_ARCHIVE')){
            // if archive tables is already made stop the precess
            if($this->isArchived()){
                $this->comment("\nArchive tables already created.");
            }else{
                // steps of progress bar
                $steps = 3;
                $bar = $this->output->createProgressBar($steps);

                // Create post types tables
                $this->comment("\nCreating post types tables...");
                $postTypes = PostType::all();
                foreach($postTypes as $postType){
                    PostType::createTable($postType->slug, json_decode($postType->fields), "mysql_archive");
                }
                $this->info("\nPost types table created successfully!");
                $bar->advance();

                // create category relations table
                $this->comment("\n\nCreating category relations table...");
                Schema::connection("mysql_archive")->create('categories_relations', function(Blueprint $table) {
                    $table->increments('categoryRelationID');
                    $table->integer('categoryID');
                    $table->integer('belongsToID');
                    $table->string('belongsTo',55);
                    $table->timestamps();
                });
                $this->info("\nCategory relations table created successfully!");
                $bar->advance();

                // create tags relations table
                $this->comment("\n\nCreating tags relations table...");
                Schema::connection("mysql_archive")->create('tags_relations', function(Blueprint $table) {
                    $table->increments('tagRelationID');
                    $table->integer('tagID');
                    $table->integer('belongsToID');
                    $table->string('belongsTo',55);
                    $table->string('language',5);
                    $table->timestamps();
                });
                $this->info("\nTags relations table created successfully!");
                $bar->advance();
            }
        }else{
            $this->comment("\nArchive is disabled in env file.");
        }
    }

    /**
     * @return bool
     */
    private function isArchived(){
        $categories_relations = Schema::connection("mysql_archive")->hasTable('categories_relations');
        $tags_relations = Schema::connection("mysql_archive")->hasTable('tags_relations');
        if($categories_relations || $tags_relations){
            return true;
        }
        return false;
    }
}
