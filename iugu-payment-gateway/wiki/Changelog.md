## 2.0.1
* **Melhoria**: Tradução do plugin para o português (PT-BR).

## 2.0.0
* **Melhoria**: Removidas as funções deprecadas do WooCommerce.
* **Correção**: Função responsável por identificar se o cliente é uma empresa não funcionava apropriadamente.

## 1.0.14
* **Melhoria**: Erros da API da iugu mais claros na página de checkout.

## 1.0.13
* **Novidade**: As chamadas da API da iugu agora identificam o nome e a versão do plugin, o que nos ajuda a entender melhor o uso do **iugu WooCommerce** e facilita o debug.

## 1.0.11
* **Melhoria**: Erros da API da iugu agora são exibidos na página do checkout em vez do antigo erro padrão de pagamento, que dizia muito sem dizer nada.
* **Correção**: Plugin não enviava o *Bairro* do cliente, informação obrigatória para a criação de boletos registrados, impedindo a compra.

## 1.0.10
* **Correção**: ID de pagamento das assinaturas de cartão de crédito.
* **Melhoria**: Funcionamento para pessoa jurídica, enviando o nome da empresa.

## 1.0.9
* **Correção**: Campo de número de telefone.

## 1.0.8
* **Novidade**: Suporte para WooCommerce 2.6+.
* **Novidade**: Suporte para WooCommerce Subscriptions 2.0+.
* **Novidade**: Suporte para assinaturas.
* **Correção** Exibição de CPF/CPNJ em boletos.

## 1.0.7
* **Melhoria**: Geração das faturas, garantindo que sejam papgas apenas com cartão de crédito ou boleto, sem poder mudar a forma de pagamento.

## 1.0.6
* **Melhoria**: Conversão de valores para centavos antes de enviá-los para a API da iugu.
* **Melhoria**: Campo de "Nome impresso no cartão" do formulário de cartão de crédito.
* **Correção**: Carregamento do JavaScript das opções de cartão de crédito quando instalado o WooCommerce Subscriptions.
* **Correção**: HTML das instruções do cartão de crédito após o pagamento.

## 1.0.5
* **Correção**: Opção de repasse de juros quando desativada.

## 1.0.4
* **Correção**: Parcelas exibidas na versões 2.1.x do WooCommerce.

## 1.0.3
* **Melhoria**: Fluxo de pagamento com cartão de crédito.
* **Correção**: Mudança de status quando o cartão é recusado.
* **Melhoria**: Opções padrões do plugin.
* **Correção**: URLs das notificações.
* **Melhoria**: Link de _Configurações_ na página de plugins.

## 1.0.2
* **Melhoria**: Renovação de assinaturas no WooCommerce Subscription.

## 1.0.1
* **Adição**: Opção para configurar a taxa de transação que é utilizada no repasse de juros do parcelamento.

## 1.0.0
* Lançamento da versão inicial.
