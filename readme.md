# Módulo de Pré Order

Crie cotações (pré orders) no painel administrativo de sua loja e envie automaticamente um email ao consumidor para concluir o pagamento diretamente em seu checkout (com login automático).

## Badges
[![Magento - Coding Quality](https://github.com/elisei/module-pre-order/actions/workflows/magento-coding-quality.yml/badge.svg)](https://github.com/elisei/module-pre-order/actions/workflows/magento-coding-quality.yml)
[![Magento - Mess Detector](https://github.com/elisei/module-pre-order/actions/workflows/mess-detector.yml/badge.svg)](https://github.com/elisei/module-pre-order/actions/workflows/mess-detector.yml)
[![Magento - Php Stan](https://github.com/elisei/module-pre-order/actions/workflows/phpstan.yml/badge.svg)](https://github.com/elisei/module-pre-order/actions/workflows/phpstan.yml)
[![ES Lint](https://github.com/elisei/module-pre-order/actions/workflows/ESLint.yml/badge.svg)](https://github.com/elisei/module-pre-order/actions/workflows/ESLint.yml)

## Recursos

- Gere pedidos no admin
- Notifique o cliente por email ou copie a url
- Permite cadastrar o consumidor
- Se optar por criar a conta automaticamente o consumidor faz login automático (sem senha)
- Gere pedidos personalizados com preços especias caso queira

## Instalação

```ssh
composer require o2ti/pre-order
```

Após a instalação pelo Composer, execute os seguintes comandos:

```sh
bin/magento setup:upgrade
bin/magento setup:di:compile
```

