# Pagseguro para Laravel

# Como instalar via composer
composer require willian/pagseguro

# Registre o provider
Em config/app.php adicione o provider "Nagahshi\Pagseguro\PagseguroServiceProvider::class"

# Publique o config do pagseguro
Execute o comando php artisan vendor:publish

# Configurando suas Credenciais
Pode ser cirado as chaves via .ENV ou acesse config/pagseguro.php e configure com seus dados veja o modelo
return [
  'email' => env('PAGSEGURO_EMAIL', 'email@frompagseguro.com'),
  'token' => env('PAGSEGURO_TOKEN', 'tokenpagseguro'),
  'redirect_url' => env('PAGSEGURO_REDIRECT_URL','https://meusite.plus/')
];

# Fazendo request de pagamento exemplo apenas
public function payment(Nagahshi\Pagseguro\Gateway $gateway){
  # setando dados do cliente
  $gateway->setClient([
    'senderName' => 'Joao comprador',
    'senderEmail' => 'comprador@uol.com',
    'senderClient' => 1
  ]);
  # setando item
  $gateway->setItems([
    ['itemDescription' =>'Meu produto ','itemAmount' => 100.00]
  ]);
  # fazendo request da url de pagamento e definindo rota de retorno
  $url = $gateway->requestURLPayment(url('/'));
  # redirecionando para pagamento
  return redirect()->to($url);
}

# Callback do pagamento
Após definir no pagseguro sua rota de notificação colete a variavel que veio via  POST 'notificationCode'
e utilize a função callback que irá lhe retornar o código do cliente e os produtos adiquiridos.
