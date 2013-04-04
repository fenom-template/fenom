Benchmark
=========

To start benchmark run script `benchmark/run.php`.

### Smarty3 vs Twig vs Aspect

    Print varaibles

     smarty3: !compiled and !loaded      8.7919 sec,       21.1 MiB
     smarty3:  compiled and !loaded      0.0341 sec,       16.4 MiB
     smarty3:  compiled and  loaded      0.0028 sec,       16.4 MiB

        twig: !compiled and !loaded      3.9040 sec,       67.5 MiB
        twig:  compiled and !loaded      0.0337 sec,       16.1 MiB
        twig:  compiled and  loaded      0.0027 sec,       16.1 MiB

      cytro: !compiled and !loaded      1.0142 sec,        8.8 MiB
      cytro:  compiled and !loaded      0.0167 sec,        6.1 MiB
      cytro:  compiled and  loaded      0.0024 sec,        6.1 MiB

    Iterates array

     smarty3: !compiled and !loaded      0.0369 sec,        5.7 MiB
     smarty3:  compiled and !loaded      0.0048 sec,        3.1 MiB
     smarty3:  compiled and  loaded      0.0039 sec,        3.1 MiB

        twig: !compiled and !loaded      0.0810 sec,        4.3 MiB
        twig:  compiled and !loaded      0.0605 sec,        2.9 MiB
        twig:  compiled and  loaded      0.0550 sec,        2.9 MiB

      cytro: !compiled and !loaded      0.0093 sec,        3.0 MiB
      cytro:  compiled and !loaded      0.0033 sec,        2.4 MiB
      cytro:  compiled and  loaded      0.0027 sec,        2.4 MiB

    templates inheritance

     smarty3: !compiled and !loaded      0.6374 sec,        9.8 MiB
     smarty3:  compiled and !loaded      0.0009 sec,        3.0 MiB
     smarty3:  compiled and  loaded      0.0001 sec,        3.0 MiB

        twig: !compiled and !loaded      0.5568 sec,       11.1 MiB
        twig:  compiled and !loaded      0.0255 sec,        6.3 MiB
        twig:  compiled and  loaded      0.0038 sec,        6.3 MiB

      cytro: !compiled and !loaded      0.1222 sec,        3.9 MiB
      cytro:  compiled and !loaded      0.0004 sec,        2.4 MiB
      cytro:  compiled and  loaded      0.0000 sec,        2.4 MiB

* **!compiled and !loaded** - template engine object created but parsers not initialized and templates not compiled
* **compiled and !loaded** - template engine object created, template compiled but not loaded
* **compiled and  loaded** - template engine object created, template compiled and loaded