# Chamado em Cascata

Este plugin tem como intuito permitir a geração de fluxos de chamados em cascata, utilizando os chamados modelos presentes no GLPI, dessa forma é possível gerar fluxos de chamados pre-definidos utilizando de uma categoria para usar como trigger da regra de fluxo de chamado, este documento serve para dar contexto para a utilização do plugin como um todo.

## Utilização

O usuário irá criar uma nova regra na página de regras do plugin, nessa regra será definido nome da regra, tal como uma `Categoria ITIL` para ser usada como gatilho da regra.

Após a criação da regra, o usuário irá definir o fluxo/comportamento da regra, selecionando quais chamados devem ser abertos quando a regra for executada, para além disso, a regra pode possuir sub-filhos, que só serão abertos após a solução do filho, isso permite encadeamento de chamados, criando um fluxo complexo de utilização.

## Download

Versões podem ser baixadas em [GitHub](https://github.com/plss-github/ticketcascade/releases).

## Copyright

* **Code**: you can redistribute it and/or modify it under the terms of the GNU General Public License ([GPL-2.0](https://www.gnu.org/licenses/gpl-2.0.en.html)).
