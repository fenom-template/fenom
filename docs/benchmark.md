Benchmark
=========

To start benchmark use script `benchmark/run.php -h`.

### Smarty3 vs Twig vs Fenom

Smarty3 vs Twig vs Fenom

Generate templates... Done

    Testing a lot output...
     smarty3: !compiled and !loaded      3.9101 sec,       15.1 MiB
     smarty3:  compiled and !loaded      0.0235 sec,        9.3 MiB
     smarty3:  compiled and  loaded      0.0015 sec,        9.3 MiB
    
        twig: !compiled and !loaded      1.8725 sec,       68.9 MiB
        twig:  compiled and !loaded      0.0337 sec,       17.0 MiB
        twig:  compiled and  loaded      0.0013 sec,       17.0 MiB
    
       fenom: !compiled and !loaded      0.3157 sec,        8.9 MiB
       fenom:  compiled and !loaded      0.0159 sec,        6.6 MiB
       fenom:  compiled and  loaded      0.0012 sec,        6.6 MiB
    
    
    Testing 'foreach' of big array...
     smarty3: !compiled and !loaded      0.0355 sec,        5.8 MiB
     smarty3:  compiled and !loaded      0.0032 sec,        3.1 MiB
     smarty3:  compiled and  loaded      0.0024 sec,        3.1 MiB
    
        twig: !compiled and !loaded      0.0799 sec,        4.7 MiB
        twig:  compiled and !loaded      0.0065 sec,        3.2 MiB
        twig:  compiled and  loaded      0.0054 sec,        3.5 MiB
    
       fenom: !compiled and !loaded      0.0459 sec,        3.1 MiB
       fenom:  compiled and !loaded      0.0024 sec,        2.5 MiB
       fenom:  compiled and  loaded      0.0017 sec,        2.5 MiB
    
    
    Testing deep 'inheritance'...
     smarty3: !compiled and !loaded      0.3984 sec,       10.2 MiB
     smarty3:  compiled and !loaded      0.0009 sec,        3.1 MiB
     smarty3:  compiled and  loaded      0.0001 sec,        3.1 MiB
    
        twig: !compiled and !loaded      0.2897 sec,       11.2 MiB
        twig:  compiled and !loaded      0.0197 sec,        6.5 MiB
        twig:  compiled and  loaded      0.0019 sec,        6.5 MiB
    
       fenom: !compiled and !loaded      0.0546 sec,        3.2 MiB
       fenom:  compiled and !loaded      0.0005 sec,        2.5 MiB
       fenom:  compiled and  loaded      0.0000 sec,        2.5 MiB

* **!compiled and !loaded** - template engine object created but parsers not initialized and templates not compiled
* **compiled and !loaded** - template engine object created, template compiled but not loaded
* **compiled and  loaded** - template engine object created, template compiled and loaded

### Stats

| Template Engine | Files  | Classes  |  Lines |
| --------------- | ------:| --------:| ------:|
| Smarty3 (3.1.13)|    320 |      190 |  55095 |
| Twig (1.13.0)   |    162 |      131 |  13908 |
| Fenom (1.0.1)   |      9 |       16 |   3899 |
