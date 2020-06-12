---
date: "2020-06-12 08:00:00"
tags: ["Symfony", "Twig", "svg", "graph", "graphique"]
title: "Des graphiques dans Symfony avec Twig et SVG"
description: "A-t-on toujours besoin d'une librairie javascript complexe pour génerer de jolis graphiques dans nos applications Symfony ? Regardons ce que le format SVG peut faire pour nos représentations de données, ce qu'il apporte et comment il s'intègre (bien) dans notre stack Symfony / Twig."
language: fr
cover: /img/articles/symfony-twig-svg/cover.png
---

Pour afficher des graphiques et diagrammes dans nos applications Symfony, il existe une large offre de solutions en Javascript qui se chargent du rendu coté client.

Mais je vous propose ici une alternative _low-tech_, coté serveur, qui fait appel à un standard depuis longtemps éprouvé : SVG.

<svg class="pie" width="600" height="300"  viewBox="-182 -110 364 220" xmlns="http://www.w3.org/2000/svg">
  <style type="text/css">
    svg.pie .portion:nth-of-type(5n+1) { fill: #FFCDB2; }
    svg.pie .portion:nth-of-type(5n+2) { fill: #FFB4A2; }
    svg.pie .portion:nth-of-type(5n+3) { fill: #E5989B; }
    svg.pie .portion:nth-of-type(5n+4) { fill: #B5838D; }
    svg.pie .portion:nth-of-type(5n+0) { fill: #6D6875; }

    svg.pie .label {
      font-size: 12px;
      fill: #666666;
    }
    svg.pie .label-line {
      stroke: lightgrey;
    }
    svg.pie .portion {
      transition: transform 200ms ease-in-out;
    }
    svg.pie .portion:hover {
      transform: scale(1.05);
    }
  </style>
  <line class="label-line" x1="36.812455268468" y1="92.977648588825" x2="180" y2="92.977648588825" />
  <text class="label" x="102" y="104.97764858883">Abricot : 38%</text>
  <path class="portion" d="M0,0 L100,0 A100,100 1 0,1 -72.896862742141,68.454710592869 Z" />
  <line class="label-line" x1="-98.228725072869" y1="18.738131458573" x2="-180" y2="18.738131458573" />
  <text class="label" x="-180" y="30.738131458573">Melon : 18%</text>
  <path class="portion" d="M0,0 L-72.896862742141,68.454710592869 A100,100 1 0,1 -92.977648588825,-36.812455268468 Z"/>
  <line class="label-line" x1="-83.580736136827" y1="-54.902281799813" x2="-180" y2="-54.902281799813" />
  <text class="label" x="-180" y="-42.902281799813">Pêche : 6.5%</text>
  <path class="portion" d="M0,0 L-92.977648588825,-36.812455268468 A100,100 1 0,1 -70.710678118655,-70.710678118655 Z"/>
  <line class="label-line" x1="-33.873792024529" y1="-94.088076895423" x2="-180" y2="-94.088076895423" />
  <text class="label" x="-180" y="-82.088076895423">Figue : 14%</text>
  <path class="portion" d="M0,0 L-70.710678118655,-70.710678118655 A100,100 1 0,1 9.4108313318514,-99.556196460308 Z" />
  <line class="label-line" x1="73.963109497861" y1="-67.301251350977" x2="180" y2="-67.301251350977" />
  <text class="label" x="102" y="-55.301251350977">Prune : 23.5%</text>
  <path class="portion" d="M0,0 L9.4108313318514,-99.556196460308 A100,100 1 0,1 100,-1.1331077795296E-13 Z"/>
</svg>

## Qu'est-ce que SVG ?

SVG, pour _Scalable Vector Graphics_, c'est un format d'image un peu particulier puisque son contenu est décrit non pas par une grille de pixels mais par du texte et plus précisément un arbre **XML**.

Une image SVG se compose d'une zone de travail (`viewBox`) dont les dimensions sont absolues (pas d'unité) dans lesquels sont placées des formes géométriques plus ou moins complexes : rectangle, cercles, lignes, courbes, etc.

Chaque forme est décrite par un noeud XML qui porte des informations de position, de taille, de couleur, de contour, d'épaisseur de trait, etc.

> Un exemple ?

```xml
<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <rect x="10" y="25" width="60" height="60" fill="red" />
    <circle cx="55" cy="40" r="32" stroke="blue" fill="none" stroke-width="10" />
</svg>
```

Le code ci dessus décris l'image suivante :

<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <rect x="10" y="25" width="60" height="60" fill="red" />
    <circle cx="55" cy="40" r="32" stroke="blue" fill="none" stroke-width="10" />
</svg>

Ses composants géométriques font du SVG un format **particulièrement adapté à la réalisation de graphiques.**

## Twig + SVG = <3

Coté serveur, nous travaillons ici avec Symfony. Et le format SVG nous intéresse donc, puisque Twig est _conçu_ pour générer des balises au format XML !

Le moteur de template de Symfony se marie donc parfaitement avec SVG :

```twig
{# consommation.svg.twig #}
<svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
    {% for value in data %}
      <rect x="{{ loop.index }}" y="0" width="5" height="{{ value }}" />
    {% endfor %}
</svg>
```

Nous allons bénéficier de toute la puissance de Twig pour générer nos diagrammes :

- Nos données métier dans des variables;
- Des boucles, des conditions et des macros;
- La traduction ! _On peut par exemple fournir un graphique dont la légende s'adapte à la langue demandée dans la requête._

### Servir une image SVG avec Symfony

Une fois notre image SVG générée, il va falloir l'afficher.

Pour cela il nous suffit renvoyer une réponse de type `image/svg+xml` à travers une route dédiée :

```php
# GraphController.php
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/consommation.svg", name="consommation")
 */
public function consommation()
{
    return $this->render(
        'graph/consommation.svg.twig',
        [ 'data' => $user->getConsommation() ],
        new Response('', 200, ['Content-Type' => 'image/svg+xml'])
    );
}
```

Pour l'utiliser ensuite dans une balise image via son url :

```twig
<img src="{{ path('consommation') }}" />
```

Ou dans tout autre contexte utilisant une image servie via HTTP :

```css
background-image: url('/consommation.svg');
```

_💡 On peut aussi déclarer le format `svg` dans Symfony pour ajouter automatiquement le header `Content-Type` adapté à toutes nos routes SVG:_

```yaml
# config/packages/framework.yaml
framework:
    request:
        formats:
            svg: 'image/svg+xml'
```

```php
# GraphController.php
/**
 * @Route("/pie.svg", name="pie", defaults={"_format":"svg"})
 */
```

### Intégrer une image SVG dans une page

HTML supporte également un noeud SVG dans le DOM de la page. ✌️

On peut donc intéger notre graphique directement dans un template Twig HTML :

```twig
{# dashboard.html.twig #}
{% block body %}
<div>
    <h3>Mon activité</h3>
    {% include 'consommation.svg.twig' with { data: user.consommation } %}
</div>
{% endblock %}
```

Dans ce cas là, chacun des éléments géométrique de notre graphique est un élément DOM à part entière de la page.

Cela va notamment nous permettre  d'interagir avec le graphique au survol ou au clic (pour afficher une légende par exemple).

Voila ce que ça peut donner :

<svg class="histogram" width="640" height="180" viewBox="-0 -240 1000 280" xmlns="http://www.w3.org/2000/svg"><style type="text/css">
svg text { font-family: 'Cabin', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-weight: bold; }
svg.histogram .bar { fill: #B5838D; }
svg.histogram g:hover .bar { fill: #b16575; }
svg.histogram .axe { stroke: #6D6875; stroke-width: 0.6; }
svg.histogram .legend { font-size: 16px; fill: #6D6875; }
svg.histogram .percent { fill: white; }
svg.histogram .label { fill: white; line-height: 14px; font-size: 14px; letter-spacing: 0.1em; }
svg.histogram .label-box { fill: #6D6875; }
svg.histogram g .label, svg.histogram g .label-box  { opacity: 0; transition: opacity 200ms linear; }
svg.histogram g:hover .label, svg.histogram g:hover .label-box  { opacity: 1; }
  </style><line class="axe" x1="-0%" x2="100%" y1="0" y2="0" /><g><rect class="bar" x="18.75" y="-114.41441441441" width="45.833333333333" height="114.41441441441" /><path class="label-box" d="M41.666666666667 -119.41441441441 L36.666666666667 -124.41441441441 L8.1666666666667 -124.41441441441 L8.1666666666667 -153.41441441441 L75.166666666667 -153.41441441441 L75.166666666667 -124.41441441441 L46.666666666667 -124.41441441441 Z"/><text class="label" x="41.666666666667" y="-134.41441441441" text-anchor="middle">127€</text></g><text class="legend" x="41.666666666667" y="20" text-anchor="middle">JANV.</text><g><rect class="bar" x="102.08333333333" y="-102.7027027027" width="45.833333333333" height="102.7027027027" /><path class="label-box" d="M125 -107.7027027027 L120 -112.7027027027 L91.5 -112.7027027027 L91.5 -141.7027027027 L158.5 -141.7027027027 L158.5 -112.7027027027 L130 -112.7027027027 Z"/><text class="label" x="125" y="-122.7027027027" text-anchor="middle">114€</text></g><text class="legend" x="125" y="20" text-anchor="middle">FÉVR.</text><g><rect class="bar" x="185.41666666667" y="-88.288288288288" width="45.833333333333" height="88.288288288288" /><path class="label-box" d="M208.33333333333 -93.288288288288 L203.33333333333 -98.288288288288 L179.08333333333 -98.288288288288 L179.08333333333 -127.28828828829 L237.58333333333 -127.28828828829 L237.58333333333 -98.288288288288 L213.33333333333 -98.288288288288 Z"/><text class="label" x="208.33333333333" y="-108.28828828829" text-anchor="middle">98€</text></g><text class="legend" x="208.33333333333" y="20" text-anchor="middle">MARS</text><g><rect class="bar" x="268.75" y="-100.9009009009" width="45.833333333333" height="100.9009009009" /><path class="label-box" d="M291.66666666667 -105.9009009009 L286.66666666667 -110.9009009009 L258.16666666667 -110.9009009009 L258.16666666667 -139.9009009009 L325.16666666667 -139.9009009009 L325.16666666667 -110.9009009009 L296.66666666667 -110.9009009009 Z"/><text class="label" x="291.66666666667" y="-120.9009009009" text-anchor="middle">112€</text></g><text class="legend" x="291.66666666667" y="20" text-anchor="middle">AVR.</text><g><rect class="bar" x="352.08333333333" y="-121.62162162162" width="45.833333333333" height="121.62162162162" /><path class="label-box" d="M375 -126.62162162162 L370 -131.62162162162 L341.5 -131.62162162162 L341.5 -160.62162162162 L408.5 -160.62162162162 L408.5 -131.62162162162 L380 -131.62162162162 Z"/><text class="label" x="375" y="-141.62162162162" text-anchor="middle">135€</text></g><text class="legend" x="375" y="20" text-anchor="middle">MAI</text><g><rect class="bar" x="435.41666666667" y="-140.54054054054" width="45.833333333333" height="140.54054054054" /><path class="label-box" d="M458.33333333333 -145.54054054054 L453.33333333333 -150.54054054054 L424.83333333333 -150.54054054054 L424.83333333333 -179.54054054054 L491.83333333333 -179.54054054054 L491.83333333333 -150.54054054054 L463.33333333333 -150.54054054054 Z"/><text class="label" x="458.33333333333" y="-160.54054054054" text-anchor="middle">156€</text></g><text class="legend" x="458.33333333333" y="20" text-anchor="middle">JUIN</text><g><rect class="bar" x="518.75" y="-200" width="45.833333333333" height="200" /><path class="label-box" d="M541.66666666667 -205 L536.66666666667 -210 L508.16666666667 -210 L508.16666666667 -239 L575.16666666667 -239 L575.16666666667 -210 L546.66666666667 -210 Z"/><text class="label" x="541.66666666667" y="-220" text-anchor="middle">222€</text></g><text class="legend" x="541.66666666667" y="20" text-anchor="middle">JUIL.</text><g><rect class="bar" x="602.08333333333" y="-178.37837837838" width="45.833333333333" height="178.37837837838" /><path class="label-box" d="M625 -183.37837837838 L620 -188.37837837838 L591.5 -188.37837837838 L591.5 -217.37837837838 L658.5 -217.37837837838 L658.5 -188.37837837838 L630 -188.37837837838 Z"/><text class="label" x="625" y="-198.37837837838" text-anchor="middle">198€</text></g><text class="legend" x="625" y="20" text-anchor="middle">AOÛT</text><g><rect class="bar" x="685.41666666667" y="-104.5045045045" width="45.833333333333" height="104.5045045045" /><path class="label-box" d="M708.33333333333 -109.5045045045 L703.33333333333 -114.5045045045 L674.83333333333 -114.5045045045 L674.83333333333 -143.5045045045 L741.83333333333 -143.5045045045 L741.83333333333 -114.5045045045 L713.33333333333 -114.5045045045 Z"/><text class="label" x="708.33333333333" y="-124.5045045045" text-anchor="middle">116€</text></g><text class="legend" x="708.33333333333" y="20" text-anchor="middle">SEPT.</text><g><rect class="bar" x="768.75" y="-86.486486486486" width="45.833333333333" height="86.486486486486" /><path class="label-box" d="M791.66666666667 -91.486486486486 L786.66666666667 -96.486486486486 L762.41666666667 -96.486486486486 L762.41666666667 -125.48648648649 L820.91666666667 -125.48648648649 L820.91666666667 -96.486486486486 L796.66666666667 -96.486486486486 Z"/><text class="label" x="791.66666666667" y="-106.48648648649" text-anchor="middle">96€</text></g><text class="legend" x="791.66666666667" y="20" text-anchor="middle">OCT.</text><g><rect class="bar" x="852.08333333333" y="-82.882882882883" width="45.833333333333" height="82.882882882883" /><path class="label-box" d="M875 -87.882882882883 L870 -92.882882882883 L845.75 -92.882882882883 L845.75 -121.88288288288 L904.25 -121.88288288288 L904.25 -92.882882882883 L880 -92.882882882883 Z"/><text class="label" x="875" y="-102.88288288288" text-anchor="middle">92€</text></g><text class="legend" x="875" y="20" text-anchor="middle">NOV.</text><g><rect class="bar" x="935.41666666667" y="-97.297297297297" width="45.833333333333" height="97.297297297297" /><path class="label-box" d="M958.33333333333 -102.2972972973 L953.33333333333 -107.2972972973 L924.83333333333 -107.2972972973 L924.83333333333 -136.2972972973 L991.83333333333 -136.2972972973 L991.83333333333 -107.2972972973 L963.33333333333 -107.2972972973 Z"/><text class="label" x="958.33333333333" y="-117.2972972973" text-anchor="middle">108€</text></g><text class="legend" x="958.33333333333" y="20" text-anchor="middle">DÉC.</text></svg>

### Définir le style d'un graphique SVG

Comme pour n'importe quel élément DOM, les propriétés de style d'un élément SVG (remplissage, bordure, couleur, etc.) peuvent être définis dans une feuille de style CSS.

Cette feuille de style peut être :
- **Interne** : dans une balise `<style>` à l'intérieur de la balise `<svg>` (obligatoire dans le cas d'une [utilisation sous forme d'image](#servir-une-image-svg)).
- **Externe** : dans la feuille de style globale de votre site (dans le cas du SVG injecté directement dans le DOM HTML).

Nous pouvons cibler les éléments de notre graphique à l'aide du nom des éléments (svg, rect, ...), des attributs class et id, ou de tout autre sélecteur CSS. Comme avec n'importe quel élément HTML finalement.

Nous utiliserons par contre les propriété natives des éléments SVG comme `fill` et `stroke-width` directement dans le CSS, en lieu et place des habituels `background-color` et autres `border` :

```css
/* style.css */

svg.histogram .bar {
  fill: #B5838D;
}
svg.histogram .bar:hover {
  fill: #b16575;
}
svg.histogram .axe {
  stroke: #6D6875;
  stroke-width: 0.6;
}
```

💡 _Note : oui les animations et transitions CSS sont très bien supportées sur les propriétés SVG, soyons créatifs !_ 😏

## Bilan

Quels-sont les avantages de l'utilisation de SVG coté serveur pour générer nos graphiques ?

- **Poids modeste** : un graphique au format SVG est généralement assez léger, surtout face à son équivalent en bitmap.
- **Mise en cache** : un graphique généré par le serveur peut être mis en cache et servi à tous les utilisateurs (contrairement à un rendu coté client sur chaque navigateur).
- **Sur mesure** : faire ses diagrammes soi-même permet de coller exactement à la charte de son produit plutôt que de subir l'identité visuelle d'une librairie tierce.
- **Haute définition** : le SVG étant vectoriel, les graphiques SVG seront rendu en haute définition sur tous les supports, que ce soit sur des écrans haute densité ou lors de l'impression.
- **Low tech** : le SVG est une _bonne vielle techno_™️, supporté par les plus vieux navigateurs et bien documentée. J'ai même fait tourner mes graphiques sur une liseuse 😊

Je ne suis d'ailleurs pas le seul à penser que c'est une bonne idée, certains des diagrammes que nous côtoyons quotidiennement sont générés en SVG :


![Github graph activity](/img/articles/symfony-twig-svg/github-activity.png)
<legend>Le graph d'activité de Github</legend>

![Symfony profiler](/img/articles/symfony-twig-svg/symfony-profiler-4.3.png)
<legend>Le profiler de Symfony</legend>

## À vous de jouer !

Le combo SVG + Twig ne répondra pas à tous vos besoin de _data visualisation_, notamment pour les plus interactifs.

Mais il constitue une solution simple et solide pour des dashboards, diagrammes et autres rapports, dans vos projets Symfony. Je vous recommande de l'envisager pour votre prochain besoin de ce genre !

Enfin, si vous décidez de vous lancer, j'ai préparé un petit [exemple concret et fonctionnel](https://github.com/Tom32i/demo-twig-svg) avec quelques diagrammes classiques, qui peut servir de référence.

