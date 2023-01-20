WooCommerce DPD Weblabel Export
======================

Edit: ez a bővítmény már nem biztos, hogy kompatibilis a legújabb WooCommerce-el, de helyette használhatod az univerzális csomagpontos/címkenyomtatós bővítményem: https://visztpeter.me/woocommerce-csomagpont-integracio/

Szállítási adatok exportálása DPD Weblabel programhoz WooCommerce-ből.

Funkciók:
* A WooCommerce / Rendelések oldalon megjelenik egy gomb rendelésállapot szűrők alatt DPD Export néven. Erre kattintva egy CSV fájlt generál az összes FÜGGŐBEN LÉVŐ rendelésről, amit a DPD Weblabel programjába be lehet importálni, így nem kell egyesével minden címet átvinni.
* Egyesével is letölthető a CSV fájl - a Rendelések oldalon az utolsó oszlopban megjelenik egy DPD gomb.
* A CSV letölthető a rendelésrészletező oldalról is, jobb felül
* Utánvétes rendeléskor kimenti a végösszeget is. Hogy ez menjen, a Beállítások / Szállítás alján ki kell választani, hogy melyik fizetési mód az utánvétes, így tudni fogja, hogy mikor kell az árat is lementeni.

Changelog

2.0
* Kompatiblis legújabb WooCommerce-el
* Nincsenek PHP warningok
* Frissítve a DPD logó

1.0.3
* A rendeléslistában ha ki van pipálva egy vagy több rendelés, a letöltés gomb a kiválasztottakat fogja exportálni(rendelés státusztól függetlenül)
* Fájlnévben benne van a rendelés ID-je
* A wc_dpd_weblabel_item filterrel módosítható a rendelés infója mielőtt a csv fájlbe bekerül

Telepítés:
* Töltsd le a bővítményt:  https://github.com/passatgt/wc-dpd-weblabel-export/archive/master.zip
* Wordpress-ben bővítmények / új hozzáadása menüben fel kell tölteni
* WooCommerce / Beállítások / Szállítás alján megjelennek egy új beállítást, ezt nézd meg
* Működik(ha minden jól megy)

Rendelésadatok módosítása
A wc_dpd_weblabel_item filterrel módosítható a rendelés infója mielőtt a csv fájlbe bekerül. A 2. paraméter a rendelés azonosítója(ebből lehet lekérni más adatot, ha kell). Példa:
```php
add_filter('wc_dpd_weblabel_item','check_dpd_export_item',10,2);
function check_dpd_export_item($item,$order_id) {
	//Predict
	$item[0] .= '-PREDICT';

	//referenciaszám törlése
	$item[4] = '';

	//interaktív sms szám hozzáadása
	$item[17] = $item[13];

	return $item;
}
```
