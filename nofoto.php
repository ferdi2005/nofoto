<?php
	require 'boz-mw/autoload.php';
	use \cli\Log;
	use \cli\Input;
	use \cli\Opts;
	
	$mode = cli\Input::askInput('Digita 1 se vuoi salvare i monumenti senza neanche una foto (prima di WLM) oppure 2 se vuoi leggere un file di monumenti senza neanche una foto per generare le statistiche (dopo WLM)');

	$regione = cli\Input::askInput('Nazionale o Puglia?');

	$commons = \wm\Commons::instance();
	
	if ($mode == 1) {
			// Wikidata SPARQL Query
	$query = <<<QUERY
SELECT ?item ?itemLabel ?coords ?wlmid ?image
WHERE {
?item wdt:P2186 ?wlmid ;
					wdt:P17 wd:Q38 ;
MINUS { ?item wdt:P18 ?image }
MINUS {?item wdt:P373 ?cat}
MINUS {
    ?wlmst pqv:P580 [ wikibase:timeValue ?start ; wikibase:timePrecision ?sprec ] .
    FILTER (
      # precisione 9 è l'anno
      ( ?sprec >  9 && ?start >= "2020-10-01T00:00:00+00:00"^^xsd:dateTime ) ||
      ( ?sprec < 10 && ?start >= "2021-01-01T00:00:00+00:00"^^xsd:dateTime )
    )
  }
  # esclude i monumenti che hanno una data di termine precedente all'inizio del concorso
  MINUS {
    ?wlmst pqv:P582 [ wikibase:timeValue ?end ; wikibase:timePrecision ?eprec ] .
    FILTER (
      ( ?eprec >  9 && ?end < "2020-09-01T00:00:00+00:00"^^xsd:dateTime ) ||
      ( ?eprec < 10 && ?end < "2020-01-01T00:00:00+00:00"^^xsd:dateTime )
    )
  }
  # esclude i monumenti per cui è indicata una data con un anno diverso da quello del concorso
  MINUS {
    ?wlmst pq:P585 ?date .
    FILTER ( ?date < "2020-01-01T00:00:00+00:00"^^xsd:dateTime || ?date >= "2021-01-01T00:00:00+00:00"^^xsd:dateTime )
  }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],it" }
	}
QUERY;

if ($regione == "Puglia") {
	$query = <<<QUERY
		SELECT ?item ?itemLabel ?wlmid
		WHERE {
		?item wdt:P2186 ?wlmid .
		?item wdt:P131* wd:Q1447 .
							?item wdt:P17 wd:Q38 .
		MINUS { ?item wdt:P18 ?image }
		MINUS { ?item wdt:P373 ?cat }
		MINUS {
			?wlmst pqv:P580 [ wikibase:timeValue ?start ; wikibase:timePrecision ?sprec ] .
			FILTER (
			# precisione 9 è l'anno
			( ?sprec >  9 && ?start >= "2020-10-01T00:00:00+00:00"^^xsd:dateTime ) ||
			( ?sprec < 10 && ?start >= "2021-01-01T00:00:00+00:00"^^xsd:dateTime )
			)
		}
		# esclude i monumenti che hanno una data di termine precedente all'inizio del concorso
		MINUS {
			?wlmst pqv:P582 [ wikibase:timeValue ?end ; wikibase:timePrecision ?eprec ] .
			FILTER (
			( ?eprec >  9 && ?end < "2020-09-01T00:00:00+00:00"^^xsd:dateTime ) ||
			( ?eprec < 10 && ?end < "2020-01-01T00:00:00+00:00"^^xsd:dateTime )
			)
		}
		# esclude i monumenti per cui è indicata una data con un anno diverso da quello del concorso
		MINUS {
			?wlmst pq:P585 ?date .
			FILTER ( ?date < "2020-01-01T00:00:00+00:00"^^xsd:dateTime || ?date >= "2021-01-01T00:00:00+00:00"^^xsd:dateTime )
		}	SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],it" }
			}
QUERY;
	$file = fopen("elementi" . $regione . ".txt", "w+");
	} else {
		$file = fopen("elementi.txt", "w+");
	}
		$rows = \wm\Wikidata::querySPARQL( $query );
	
		$elements = [];
		$elnum = 0;
		foreach( $rows as $row ) {
			// Mettere che si evita di procedere con file diversi
			$search = '"' . $row->wlmid->value. '"';
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
		if ($regione == "Puglia") {
			$file = fopen("elementi" . $regione . ".txt", "w+");
		}
		$new = fopen("nuovielementi.txt", "w+");
		$fileread = fread($file, filesize("elementi.txt"));
		$elements = explode("\n", $fileread);
		$elcount = count($elements);
		$total = 0;
		foreach ($elements as $element)	 {
			$el = explode(";", $element);
			$search = '"' . $el[0]. '"';
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
				$string = $element . "\n";
				fwrite($new, $string);
			}
			echo('Controllo ' . $el[1] . " - " . $total .  " monuementi con una foto in più.\n");
		}	
		echo("Monumenti che prima non avevano un'immagine: " . $elcount . "\n");
		echo("Monumenti che ora non hanno un'immagine: " . $elcount - $total . "\n");
		echo("Nuovi monumenti con immagine: " . $total . "\n");
		fclose($file);
		fclose($new);
	}

?>