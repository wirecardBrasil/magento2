# Moip para Magento 2

Integração oficial para lojas em Magento 2 via Moip API V2.

## Pre-requisitos
O módulo é compatível apenas com Magento 2.2

## Receba pagamentos transparente

  - Cartão de crédito, 7 bandeiras.
    - Visa, Mastercard, Dinners, American Express, Elo, Hiper e Hipercard 
  - Boleto Bancário
  - Transferência Bancária (TED) 

# Recursos

  - Módulo 100% transparente, todo processo de finalização é **feito no seu checkout**, sem redirecionamentos!
  - Personalização completa das regras das suas parcelamento
  - Relaciomaneto de atributos da sua loja com a nossa API
  - Retorno de status automático, com tratamento de informação de cancelamento direto em sua loja
  - Homologação máxima na PCI, toda a compra em seu site é segura! 
  


# Instalação e Configuração em minutos
 Via composer ou Magento Marketplace, você mesmo pode realizar sua instalação.
 Além disso pode nos contactar e ter todo o processo de instalação feito pelo time de forma **gratuita**!
  
# Wiki e Tutorias
Embreve nosso git você terá acesso a tutorias em video e uma [Wiki Completa][wiki]  para facilitar sua integração e instalação.

### Instalação via composer

Na pasta do projeto execute:

```composer
composer require moip/magento2
php bin/magento setup:upgrade
```

#### Recomendação:
o comando:
```composer 
composer clearcache
```
pode ser usado para limpar cache do composer. Em caso de erros na instalação acima, execute e tente novamente.

   [Wiki]: <https://github.com/moip/magento2/wiki>
