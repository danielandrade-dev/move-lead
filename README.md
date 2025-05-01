# Sistema de Venda de Leads

Sistema especializado para gerenciamento e distribuição de leads para diversos segmentos (móveis planejados, óticas, advocacia, etc), com suporte a múltiplas empresas, lojas e contratos.

## 🚀 Funcionalidades Principais

### Gestão de Leads
- Distribuição geolocalizada de leads
- Suporte a múltiplos segmentos
- Sistema de garantia com substituição automática
- Prevenção de duplicidade por telefone
- Distribuição baseada em raio de cobertura

### Empresas e Lojas
- Gestão de múltiplas empresas
- Múltiplas lojas por empresa
- Múltiplos pontos de captação por loja
- Controle de raio de cobertura por ponto

### Contratos
- Contratos por empresa e loja
- Sistema de garantia (30% padrão)
- Fechamento automático após conclusão
- Período de garantia de 7 dias
- Controle de quantidade de leads

## 💻 Requisitos Técnicos

- PHP 8.1+
- Laravel 10.x
- MySQL/PostgreSQL
- Composer
- Node.js & NPM

## 🛠 Instalação

```bash
# Clone o repositório
git clone [url-do-repositorio]

# Instale as dependências PHP
composer install

# Instale as dependências JavaScript
npm install

# Configure o ambiente
cp .env.example .env
php artisan key:generate

# Configure o banco de dados no arquivo .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seu_banco
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# Execute as migrações
php artisan migrate

# Compile os assets
npm run dev
```

## 📊 Estrutura do Banco de Dados

### Principais Tabelas
- `companies` - Empresas
- `stores` - Lojas
- `store_locations` - Pontos de captação
- `contracts` - Contratos
- `leads` - Leads
- `lead_stores` - Distribuição de leads
- `lead_warranties` - Garantias de leads

## 🔄 Fluxo de Garantia

1. Cliente solicita garantia do lead
2. Sistema analisa a solicitação
3. Se aprovada:
   - Lead é marcado para substituição
   - Sistema busca automaticamente novo lead elegível
   - Novo lead é distribuído quando disponível

## ⚙️ Configurações Importantes

### Raio de Cobertura
- Mínimo: 10km por ponto de captação
- Configurável por ponto de captação

### Controle de Duplicidade
- Verificação por telefone
- Período de restrição: 3 meses
- Aplicado por empresa/loja

### Garantia de Leads
- Percentual padrão: 30%
- Período de fechamento: 7 dias
- Substituição automática

## 🔍 Monitoramento

O sistema inclui comandos para monitoramento automático:

```bash
# Verifica contratos para fechamento automático
php artisan contracts:check-auto-close

# Processa distribuição de leads de garantia
php artisan warranty:process-distribution
```

## 📝 Comandos Úteis

```bash
# Limpar cache
php artisan cache:clear

# Atualizar classes
composer dump-autoload

# Executar testes
php artisan test
```

## 🔐 Segurança

- Validação de telefones
- Proteção contra duplicidade
- Transações em banco de dados
- Controle de acesso por usuário

## 🤝 Contribuição

1. Faça o fork do projeto
2. Crie sua feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença [MIT](https://opensource.org/licenses/MIT).

## 📧 Suporte

Para suporte, envie um email para [seu-email@dominio.com]