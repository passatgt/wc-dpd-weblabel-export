WooCommerce DPD Weblabel Export
======================

Szállítási adatok exportálása DPD Weblabel programhoz WooCommerce-ből.

Funkciók:
* A WooCommerce / Rendelések oldalon megjelenik egy gomb rendelésállapot szűrők alatt DPD Export néven. Erre kattintva egy CSV fájlt generál az összes FÜGGŐBEN LÉVŐ rendelésről, amit a DPD Weblabel programjába be lehet importálni, így nem kell egyesével minden címet átvinni.
* Egyesével is letölthető a CSV fájl - a Rendelések oldalon az utolsó oszlopban megjelenik egy DPD gomb.
* A CSV letölthető a rendelésrészletező oldalról is, jobb felül
* Utánvétes rendeléskor kimenti a végösszeget is. Hogy ez menjen, a Beállítások / Szállítás alján ki kell választani, hogy melyik fizetési mód az utánvétes, így tudni fogja, hogy mikor kell az árat is lementeni.

Telepítés:
* Töltsd le a bővítményt:  https://github.com/passatgt/wc-dpd-weblabel-export/archive/master.zip
* Wordpress-ben bővítmények / új hozzáadása menüben fel kell tölteni
* WooCommerce / Beállítások / Szállítás alján megjelennek egy új beállítást, ezt nézd meg
* Működik(ha minden jól megy)
