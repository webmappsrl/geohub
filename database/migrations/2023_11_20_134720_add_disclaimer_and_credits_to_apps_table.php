<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDisclaimerAndCreditsToAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {

            $records = DB::table('apps')->get();
            foreach ($records as $record) {
                DB::table('apps')
                    ->where('id', $record->id)
                    ->update(['page_project' => ['it' => $record->page_project]]);
            }

            $table->text('page_disclaimer')->nullable()->default('{"it":"L’escursionismo e, più in generale, l’attività all’aria aperta, è una attività potenzialmente rischiosa: prima di avventurarti in una escursione assicurati di avere le conoscenze e le competenze per farlo. Se non sei sicuro rivolgiti agli esperti locali che ti possono aiutare, suggerire e supportare nella pianificazione e nello svolgimento delle tue attività. I dati presentati su questa APP non possono garantire completamente la percorribilità senza rischi del percorso: potrebbero essersi verificati cambiamenti, anche importanti, dall’ultima verifica effettuata del percorso stesso. E’ fondamentale quindi che chi si appresta a svolgere attività valuti attentamente l’opportunità di proseguire in base ai suggerimenti e ai consigli contenuti in questa APP, in base alla propria esperienza, alle condizioni metereologiche (anche dei giorni precedenti) e di una valutazione effettuata sul campo all’inizio dello svolgimento della attività. La società Webmapp S.r.l. non fornisce garanzie sulla sicurezza dei luoghi descritti, e non si assume alcuna responsabilità per eventuali danni causati dallo svolgimento delle attività descritte.","en":"Hiking and, more generally, outdoor activities are potentially risky endeavors. Before embarking on a hike, make sure you have the knowledge and skills to do so. If you are unsure, seek assistance from local experts who can help, advise, and support you in planning and carrying out your activities. The data presented on this app cannot guarantee the complete risk-free viability of the route; changes, even significant ones, may have occurred since the last verification of the route. It is essential, therefore, that those engaging in activities carefully consider whether to proceed based on the suggestions and advice provided in this app, taking into account their own experience, weather conditions (including those of preceding days), and on-site evaluations at the beginning of the activity. Webmapp S.r.l. does not provide guarantees regarding the safety of the described locations and assumes no responsibility for any damages caused by the execution of the described activities."}');
            $table->text('page_credits')->nullable()->default('{"it":"<h3>Dati cartografici</h3><p>&copy; OpenStreetMap Contributors</p><h3>Mappa</h3><p>&copy; Webmapp, distribuita con licenza CC BY-NC-SA</p><h3>Webmapp</h3><p>Questa app &egrave; sviluppata e mantenuta da Webmapp.<br />Webmapp realizza servizi cartografici web, app mobile, e cartografia stampata per il Turismo Natura &amp; Avventura.<br />Per maggiori informazioni visitate il nostro sito web&nbsp;<a href=\"http://www.webmapp.it/\" target=\"_blank\" rel=\"noopener\">webmapp.it</a><br />o scriveteci all’indirizzo&nbsp;<a href=\"mailto:info@webmapp.it\">info@webmapp.it</a></p>","en":"<h3>Cartographic Data</h3><p>&copy; OpenStreetMap Contributors</p><h3>Map</h3><p>&copy; Webmapp, distributed under CC BY-NC-SA license</p><h3>Webmapp</h3><p>This app is developed and maintained by Webmapp.<br />Webmapp provides web mapping services, mobile apps, and printed cartography for Nature &amp; Adventure Tourism.<br />For more information, visit our website&nbsp;<a href=\"http://www.webmapp.it/\" target=\"_blank\" rel=\"noopener\">webmapp.it</a><br />or contact us at&nbsp;<a href=\"mailto:info@webmapp.it\">info@webmapp.it</a></p>"}');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {

            $records = DB::table('apps')->get();
            foreach ($records as $record) {
                DB::table('apps')
                    ->where('id', $record->id)
                    ->update(['page_project' => $record->page_project]);
            }

            $table->dropColumn(['page_disclaimer', 'page_credits']);
        });
    }
}
