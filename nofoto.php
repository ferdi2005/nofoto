<?php
	require 'boz-mw/autoload.php';
	use \cli\Log;
	use \cli\Input;
	use \cli\Opts;
	
	$mode = cli\Input::askInput('Digita 1 se vuoi salvare i monumenti senza neanche una foto (prima di WLM) oppure 2 se vuoi leggere un file di monumenti senza neanche una foto per generare le statistiche (dopo WLM)');

	$regione = cli\Input::askInput('Nazionale o Puglia?');

	$commons = \wm\Commons::instance();
	
	
	if ($mode == 1) {
		$file = fopen("elementi.txt", "w+");
			// Wikidata SPARQL Query
	$query = <<<QUERY
SELECT DISTINCT ?item ?itemLabel ?coords ?wlmid ?image
WHERE {
?item wdt:P2186 ?wlmid ;
					wdt:P17 wd:Q38 ;
MINUS { ?item wdt:P18 ?image }
MINUS {?item wdt:P373 ?cat}
		MINUS { ?item p:P2186 [ pq:P582 ?end ] .
		FILTER ( ?end <= "2020-09-01T00:00:00+00:00"^^xsd:dateTime )
					}
SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],it" }
	}
QUERY;

if ($regione == "Puglia") {
	$query = <<<QUERY
	SELECT DISTINCT ?item ?itemLabel ?wlmid
	WHERE {
	?item wdt:P2186 ?wlmid .
	?item wdt:P131* wd:Q1447 .
						?item wdt:P17 wd:Q38 .
	MINUS { ?item wdt:P18 ?image }
	MINUS { ?item wdt:P373 ?cat }
			MINUS { ?item p:P2186 [ pq:P582 ?end ] .
			FILTER ( ?end <= "2020-09-01T00:00:00+00:00"^^xsd:dateTime )}
	SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],it" }
		}

QUERY;
	}
		$rows = \wm\Wikidata::querySPARQL( $query );
	
		$elements = [];
		$elnum = 0;
		foreach( $rows as $row ) {
			// Mettere che si evita di procedere con file diversi
			$search = '"' . $row->wlmid->value. '" hastemplate:Monumento italiano';
			$response = $commons->fetch([
				'action' => 'query',
				'list' => 'search',
				'srsearch' => $search,
				'srnamespace' => '6',
				'srinfo' => "totalhits",
				"srlimit" => '1',
			]);
			$count = 0;
			foreach ($response->query->search as $r) {
				$count++;
			}

			if ($count == 0) {
				$elnum++;
				$string = $row->wlmid->value . ";" . basename($row->item->value) . "\n";
				fwrite($file, $string);
				echo("Aggiunto elemento " . $row->item->value . "\n");
			}
		}
		echo("Ho finito di preparare il file di output, esegui questo script di nuovo nella stessa cartella del file elementi.txt usando l'opzione due alla fine di WLM per generare le statistiche.\n");
		echo("Ci sono ben " . $elnum . " monumenti.\n");
		fclose($file);
	}
	if ($mode == 2) {
		$file = fopen("elementi.txt", "r");
		$fileread = fread($file, filesize("elementi.txt"));
		$elements = explode("\n", $fileread);
		$elcount = count($elements);
		$total = 0;
		foreach ($elements as $element)	 {
			$el = explode(";", $element);
			$search = '"' . $el[0]. '" hastemplate:Monumento italiano';
			echo('Controllo ' . $el[1] . "\n");
			$response = $commons->fetch([
				'action' => 'query',
				'list' => 'search',
				'srsearch' => $search,
				'srnamespace' => '6',
				'srinfo' => "totalhits",
				"srlimit" => '1',
			]);
			$count = 0;
			foreach ($response->query->search as $r) {
				$count++;
			}
			if($count > 0) {
				$total++;
			}
		}	
		echo("Monumenti che prima non avevano un'immagine: " . $elcount . "\n");
		echo("Monumenti che ora non hanno un'immagine: " . $elcount - $total . "\n");
		echo("Nuovi monumenti con immagine: " . $total . "\n");

	}

?>