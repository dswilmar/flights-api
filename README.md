# Flights API

API construída com PHP, utilizando o Framework Lumen para retornar vôos e grupos de vôos de um determinado serviço da 123Milhas.

## Como rodar

* Clone o repositório
* Faça a instalação das dependências com o composer, utilizando o comando abaixo:
````
composer install
````
* Altere o nome do arquivo .env.example para .env e faça as alterações nas configurações da aplicação caso seja necessário
* O comando abaixo irá rodar a aplicação em sua máquina local na porta 8000
````
php -S localhost:8000 -t public
````
## Rotas
Método    | Rota      | Retorno
--------- | --------- | ---------
GET       | /flights  | Retorna um JSON com os vôos e grupos de vôos gerados

## Aplicação Rodando no Heroku
<https://wilmar-flights-api.herokuapp.com/flights>
