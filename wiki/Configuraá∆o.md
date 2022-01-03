## 1. Instale o plugin ##
Envie os arquivos do plugin para a pasta `wp-content/plugins` ou instale-o usando o instalador de plugins do WordPress. Em seguida, ative o **WooCommerce iugu**.

## 2. Obtenha o ID da sua conta iugu e um API token ##
No painel da iugu, acesse o menu [*Administração > Configurações da conta*](https://app.iugu.com/account) e crie um *API token* do tipo LIVE. Ele será usado, junto com o ID da sua conta iugu, para configurar o plugin.

Você também pode criar um API token do tipo TEST para realizar testes com o plugin.

## 3. Configure o WooCommerce ##
No WordPress, acesse o menu *WooCommerce > Configurações > Produtos > Inventário* e deixe em branco a opção **Manter estoque (minutos)**.

Essa funcionalidade, introduzida na versão 2.0 do Woocommerce, permite cancelar a compra e liberar o estoque depois de alguns minutos, mas não funciona muito bem com pagamentos por boleto bancário, pois estes podem levar até 48 horas para serem validados.

## 4. Ative os pagamentos pela iugu ##

Ainda no WordPress, acesse o menu *WooCommerce > Configurações > Finalizar compra* e selecione **iugu - Cartão de crédito** ou **iugu - Boleto bancário**. Marque a caixa de seleção para ativar o(s) método(s) de pagamento que lhe interessa(m) e preencha as opções de **ID da conta** e **API Token** para cada um deles.
