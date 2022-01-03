É possível trabalhar com o plugin **WooCommerce iugu** em Sandbox (modo de testes). Dessa forma, você pode testar os processos de pagamentos por boleto e cartão antes de realizá-los em produção.

## Ativando o sandbox ##

1. Acesse o painel da iugu e, no menu [*Administração > Configurações da conta*](https://app.iugu.com/account), crie um API token do tipo TEST.
2. Agora no WordPress, acesse as configurações de cartão de crédito ou boleto bancário do WooCommerce iugu (*WooCommerce > Configurações > Finalizar compra*) e adicione o seu API token (TEST).
3. Ao fim da página, marque a caixa de seleção *Ativar o sandbox da iugu*.

Opcional: Marque também a opção *Habilitar log* para ver o registro dos seus pagamentos no menu *WooCommerce > Status > Logs*.
