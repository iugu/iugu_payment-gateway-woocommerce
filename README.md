# **Plugin Woocommerce Iugu**

Este plugin é um facilitador de integração entre Woocommerce e Iugu.

Sua finalidade é auxiliar a cobrança de Vendas como compras pontuais ou Assinaturas, oferecendo os métodos de pagamento: Cartão de Crédito, Boleto Bancário e PIX.


# **_Antes de utilizar veja nossos pré-requisitos para instalação:_**

-   **Wordpress 5.6+**
-   **PHP 7.2 +**
-   **Woocommerce 5.1 +**
-   **Brazilian Market on WooCommerce**
-   **Woocomerce Subscriptions.**


**Obs.: Somente siga os passos abaixo se estiver com os pré-requisitos corretamente configurados em seu ambiente Wordpress**
**Verifique também se todos os métodos de pagamento que irá utilizar estão habilitados em seu portal iugu****[Cartão de Crédito](https://alia.iugu.com/settings/account/credit_card/edit)**
**Por padrão, Boleto Bancário e PIX, já vem habilitado. Para o Cartão de Crédito, veja a seguinte documentação** [**clicando aqui**](https://support.iugu.com/hc/pt-br/articles/202342429-Qual-%25C3%25A9-a-documenta%25C3%25A7%25C3%25A3o-necess%25C3%25A1ria-para-aceitar-pagamento-com-cart%25C3%25A3o-de-cr%25C3%25A9dito-)**.**
## Instalando o plugin

Primeiro passo precisa realizar o download do arquivo Zip do plugin.

Em seguida, acesse seu ambiente admin do Wordpress, clique em **Plugins> Add New> Upload Plugin**:

![image](https://user-images.githubusercontent.com/25038940/132904510-46acc8c0-d97c-4b20-a5c3-a19a8bc724b6.png)


Clique em “Escolher arquivo”, insira o Zip do plugin da iugu e clique em “Instalar”:

![image](https://user-images.githubusercontent.com/25038940/132904956-b845b05c-2ea8-486a-8f5a-f93fc60f1d10.png)

Após finalizar a instalação, clique em “Activate Plugin”:

![image](https://user-images.githubusercontent.com/25038940/132904991-9f4ac546-4579-453b-8e3f-cb2ed83545ad.png)


## Configurando Plugin Woocommerce Iugu:

**Obs.: Para seguir com esta configuração, é preciso ter uma conta verificada na iugu, caso sua conta ainda não esteja verificada, solicite nossa ajuda direto no portal da iugu através de nosso chat.**

  

Primeiro você precisará acessar seu portal iugu, para resgatar sua **account_id** e criar sua **api_token,** [clique aqui](https://support.iugu.com/hc/pt-br/articles/201726767-ID-da-conta-e-tokens-de-API-de-teste-e-de-produ%25C3%25A7%25C3%25A3o) e veja como fazer.

Em seguida, se atente a quais os meios de pagamento irá ativar para seu E-commerce.

É necessário realizar a configuração em cada um deles.

Dentro da opção “WooCommerce”, clique em “Settings” e em seguida clique em “Payments”:

![image](https://user-images.githubusercontent.com/25038940/132905527-d562da5d-f605-4fa8-93cd-15b009ebd372.png)


## Vamos iniciar com as configurações do método de pagamento Cartão de Crédito:

Acesse a opção “Settings”, dentro do menu de opções do “WooCommerce”.

Na próxima tela, clique em “Payments” e selecione a opção “Manage”, que fica no canto direito das informações do “iugu - Credit card”:

![image](https://user-images.githubusercontent.com/25038940/132905583-50caab43-c697-4e06-92aa-3fd048835019.png)


Na próxima tela, precisa habilitar a utilização do Cartão de crédito, selecionando a opção “Ativar pagamentos com cartão de crédito com iugu”.

Em seguida é preciso inserir o account_id e api_token (copiados do portal da iugu):

![image](https://user-images.githubusercontent.com/25038940/132905828-2f38d50e-8d74-460c-881b-abe08c7ef520.png)

Abaixo há algumas opções personalizadas que podem ser configuradas:

![image](https://user-images.githubusercontent.com/25038940/132905866-99bb3f59-762a-4e16-b96c-3d2c32c36e8b.png)

**Ignorar e-mail devido:** Quando marcado, não enviaremos ao pagador e-mails de cobrança.

**Menor valor da parcela:** esta opção, você poderá limitar o valor mínimo de cada parcela quando habilitado a opção de parcelamento.

**Passe no interesse:** opção de repasse dos juros para o pagador (Informação é apresentada no checkout com “com juros” ou “sem juros” e informa também o cálculo que serão aplicados os juros)

**Envie apenas o total do pedido:** se selecionado, apenas apresenta o valor total do pedido e não a lista de itens comprados.

**Sandbox iugu:** usado para testar pagamentos. Não se esqueça de usar um token TEST API, que pode ser encontrado / criado nas [configurações da conta iugu](https://iugu.com/settings/account)

**Depurando:** ativando esta opção, serão gravados todos os registros de evento iugu, como as  solicitações de API. O registro pode ser encontrado em [Status do sistema> registros](https://forasteiroti.com/wp-admin/admin.php?page=wc-status&tab=logs&log_file=iugu-credit-card-13d6d776a71c003972e4b6ed4a92c603.log)

  

Por fim, clique em “Salvar alterações”:

![image](https://user-images.githubusercontent.com/25038940/132905928-178dfc7c-fbae-42bf-b081-2d0c4e462ff6.png)


## Agora, habilite o método de pagamento Boleto Bancário:

Acesse a opção “Settings”, dentro do menu de opções do “WooCommerce”.

Na próxima tela, clique em “Payments” e selecione a opção “Manage”, que fica no canto direito das informações do “iugu - Bank slip”:

![image](https://user-images.githubusercontent.com/25038940/132905963-0e975310-1b03-47f9-bcc7-57843cdccc25.png)

Assim como o meio de pagamento “cartão de crédito”, no “boleto bancário” também é preciso ativar o pagamento de boleto bancário e inserir as informações de account_id e api_token:

![image](https://user-images.githubusercontent.com/25038940/132905978-f589be08-1018-471b-aba1-db41a2d1f20f.png)


Para o método de pagamento Boleto Bancário, temos algumas configurações adicionais que dependem de seu modelo de negócio:

![image](https://user-images.githubusercontent.com/25038940/132906000-b054af53-d13b-48b6-9f2d-63c7cf103553.png)


**Ignorar e-mail devido** - quando ativado, Iugu não enviará e-mails de cobrança ao pagador.

**Habilitar desconto de Boleto** - opção para habilitar a aplicação de desconto no pagamento.

**Tipo de Desconto** - opção para definir se será desconto fixo ou desconto em Percentual.

**Valor de desconto** - campo para definir o valor do desconto.

**Prazo de pagamento padrão** - prazo para vencimento do pagamento do boleto.

**Envie apenas o total do pedido** - apresenta somente o valor total do pedido, não a lista de itens comprados.

**Sandbox iugu** - usado para testes de pagamentos. É preciso utilizar o api_token de teste, gerando direto no portal nas [configurações da conta iugu](https://iugu.com/settings/account) .

Após configurado e personalizado com seu modelo de negócio, é só salvar as alterações.


# Para finalizar as configurações dos meios de pagamentos, habilite o método de pagamento PIX


Na próxima tela, clique em “Payments” e selecione a opção “Manage”, que fica no canto direito das informações do “iugu - PIX”:

![image](https://user-images.githubusercontent.com/25038940/132906042-b2f29e83-6968-4570-aab7-52842c566c94.png)


Assim como os outros métodos de pagamento, é preciso inserir as informações de account_id e token_api e habilitar o método de pagamento:

![image](https://user-images.githubusercontent.com/25038940/132906066-e65d4148-86b0-415a-93f3-4528e8bdb086.png)

O método de pagamento PIX, também há configurações personalizáveis:

![image](https://user-images.githubusercontent.com/25038940/132906103-093267f7-6606-4de4-ad5a-17a0fb5b9ed9.png)

**Ignorar e-mail devido** - quando ativado, Iugu não enviará e-mails de cobrança ao pagador.

**Envie apenas o total do pedido -** opção para apresentar ao cliente apenas o total do pedido e não apresentar a lista de itens comprados.

**Sandbox iugu** - usado para testes de pagamentos. É preciso utilizar o api_token de teste, gerando direto no portal nas [configurações da conta iugu](https://iugu.com/settings/account) .

  

E para finalizar é só salvar as alterações.

## **Ativando a opção de “Assinaturas” nas configurações:**

Caso utilize a opção de assinaturas, é preciso configurar também na aba “Assinaturas”, dentro das configurações do WooCommerce:

![image](https://user-images.githubusercontent.com/25038940/132906146-08d48422-1f1c-452a-a01f-a17d3b73966d.png)

Selecione a opção “Habilitar assinaturas Iugu” e também configure a opção “Tipo de desconto de boleto bancário”:

![image](https://user-images.githubusercontent.com/25038940/132906173-a4d45904-cf77-4fa7-8a34-8e68b14f0a03.png)

Não se esqueça de salvar as alterações!


