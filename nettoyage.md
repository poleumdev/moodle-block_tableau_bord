<p align="right"><a href="./index"> Retour </a></p>
# Nettoyage du code

Code checker sur l'intégralité du code relève 
 * 17834 erreur(s)
 * 986 avertissement(s) 

Après suppression des codes morts et correction (soit 64 commits plus tard) nous arrivons à 
 * 0 erreurs
 * 3 avertissement(s)
 
 Les avertissements sont sur les scripts javscripts minifiés.
 
 ## Analyse qualité  

En date du 18 juin 2020  
**phpmd** indique encore les violations suivantes :

|  Nombre de violation | Source              |
|----------------------|---------------------|
|  2 | block_tableau_bord.php |
|  4 | locallib.php  |
|  4 | notif.php  |
| 24 | renderer.php |

Soit un total de 34 violations

**moodle-plugin-ci validate** réclame au moins une table préfixée par block_tableau_bord (18car)  
**moodle-plugin-ci mustache** réclame une section @template et indique un problème sur la mise en place du validateur HTML (revoir le fichier travis)  
**moodle-plugin-ci grunt** signale trop de warning




