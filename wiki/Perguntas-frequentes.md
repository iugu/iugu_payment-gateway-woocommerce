## Qual é a licença do plugin?

[GNU GPL (General Public Licence) v2](http://www.gnu.org/licenses/gpl-2.0.html).

## Do que preciso para utilizar o plugin?

Ver [Requerimentos](https://github.com/iugu/iugu-woocommerce/wiki/Requerimentos).

## Quais são as tarifas da iugu?

Conheça todas as tarifas da iugu em [iugu.com/precos](https://iugu.com/precos/).

## É possível utilizar a opção de pagamento recorrente/assinaturas?

Sim, é possível utilizar este plugin para fazer pagamentos recorrentes com o [WooCommerce Subscriptions](https://www.woothemes.com/products/woocommerce-subscriptions/).

Note que a integração não é feita com a API de pagamento recorrente da iugu: ela funciona totalmente a partir do WooCommerce Subscriptions, que fornece um controle maior sobre as assinaturas dentro da sua loja WooCommerce.

## O pedido foi pago e ficou com o status *processando*, e não *concluído*. Isso está certo?

Sim. Todo gateway de pagamento no WooCommerce deve mudar o status do pedido para *processando* no momento em que o pagamento é confirmado. O status só deve ser alterado para *concluído* após o pedido ter sido entregue.

Para produtos digitais, por padrão, o WooCommerce só permite o acesso do comprador quando o pedido tem o status *concluído*. No entanto, nas configurações do WooCommerce, na aba *Produtos*, é possível ativar a opção **Conceder acesso para download do produto após o pagamento**, liberando o download no status *processando*.
