# Questo tool non è più necessario, i dati vengono tenuti automaticamente da [https://github.com/ferdi2005/wikilovesmonuments](quest'altro tool), non verrà più mantenuto


# Monumenti senza foto
Questo tool trova i monumenti senza foto in Wiki Loves Monuments Italia. Per eseguirlo, una volta installato l'ambiente PHP su Windows, basta dare:

```
php nofoto.php
```

Verranno quindi richieste delle opzioni, in particolare si può selezionare la modalità 1, cioè salvare la lista dei monumenti che non hanno una foto (operazione da fare **prima di WLM**) oppure la modalità 2 per generare delle statistiche relative a quanti monumenti in più abbiano avuto una foto dopo questa edizione di WLM (operazione da fare **dopo WLM**).

Verrà poi richiesto se svolgere la ricerca nazionale o pugliese, basta digitare qualsiasi cosa che non sia **Puglia** per far partire la ricerca con la query nazionale.

Il tool impiegherà molto tempo per svolgere le sue operazioni, dato l'alto numero di monumenti, ma non richiede altri interventi oltre alla configurazione iniziale

È già fornito in questo repository il file di output che si ottiene usando l'opzione 1 al 9 giugno 2020, chiamato sempre *elementi.txt*. Verrà sovrascritto se eseguirete l'opzione 1 nel vostro ambiente locale.
