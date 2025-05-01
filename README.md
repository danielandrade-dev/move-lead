# Sistema de Venda de Leads

Sistema especializado para gerenciamento e distribuição de leads para diversos segmentos (móveis planejados, óticas, advocacia, etc), com suporte a múltiplas empresas, lojas e contratos.

## 🚀 Funcionalidades Principais

### Gestão de Leads
- Distribuição geolocalizada de leads
- Suporte a múltiplos segmentos com campos customizados
- Sistema de garantia com substituição automática
- Prevenção de duplicidade por telefone normalizado
- Distribuição baseada em raio de cobertura
- Validação inteligente de campos por tipo
- Formatação automática de dados

### Empresas e Lojas
- Gestão de múltiplas empresas
- Múltiplas lojas por empresa
- Múltiplos pontos de captação por loja
- Controle de raio de cobertura por ponto
- Gestão de usuários por hierarquia
- Controle de acesso granular

### Contratos
- Contratos por empresa e loja
- Sistema de garantia (30% padrão)
- Fechamento automático após conclusão
- Período de garantia de 7 dias
- Controle de quantidade de leads
- Análise automática de elegibilidade

## 💻 Requisitos Técnicos

- PHP 8.1+
- Laravel 10.x
- MySQL/PostgreSQL
- Composer
- Node.js & NPM

## 🏗 Arquitetura do Sistema

### Models Base e Traits
- `BaseModel`: Classe base com funcionalidades comuns
- `HasCommonAttributes`: Trait para atributos compartilhados
- `HasGeolocation`: Trait para funcionalidades de geolocalização

### Principais Models
- `Company`: Gestão de empresas
- `Store`: Gestão de lojas
- `StoreLocation`: Pontos de captação
- `Contract`: Contratos e garantias
- `Lead`: Gestão de leads
- `LeadStore`: Distribuição de leads
- `LeadWarranty`: Sistema de garantias
- `LeadCustomField`: Campos customizados por segmento
- `Segments`: Gestão de segmentos
- `SegmentField`: Campos configuráveis por segmento
- `User`: Gestão de usuários e permissões

## 📊 Estrutura do Banco de Dados

### Principais Tabelas e Relacionamentos
```
companies
  ├── stores
  │     ├── store_locations
  │     └── contracts
  └── users

leads
  ├── lead_phones
  ├── lead_stores
  │     └── lead_warranties
  └── lead_custom_fields

segments
  └── segment_fields
```

## 🔄 Fluxos do Sistema

### Fluxo de Leads
1. Recebimento do lead
2. Normalização de telefones
3. Validação de campos customizados
4. Verificação de duplicidade
5. Distribuição geolocalizada

### Fluxo de Garantia
1. Solicitação de garantia
2. Análise automática de elegibilidade
3. Aprovação/Rejeição
4. Substituição automática
5. Monitoramento de status

## ⚙️ Configurações e Validações

### Validações Implementadas
- Telefones normalizados
- Campos customizados por tipo
- Coordenadas geográficas
- Limites de contratos
- Períodos de garantia

### Controle de Duplicidade
- Verificação por telefone normalizado
- Período de restrição: 3 meses
- Escopo por empresa/loja
- Cache de verificações

## 🔐 Segurança e Permissões

### Níveis de Acesso
- Administrador: Acesso total
- Gerente: Gestão de empresa
- Loja: Acesso restrito à loja
- Analista: Análise de garantias

### Proteções Implementadas
- Soft Delete em registros críticos
- Transações em operações complexas
- Validações em múltiplas camadas
- Logs de ações importantes

## 🛠 Comandos e Manutenção

```bash
# Verificar contratos
php artisan contracts:check-auto-close

# Processar garantias
php artisan warranty:process-distribution

# Limpar cache
php artisan cache:clear

# Executar testes
php artisan test
```

## 📝 Boas Práticas Implementadas

- Tipagem forte em todos os models
- Documentação PHPDoc completa
- Validações centralizadas
- Código limpo e organizado
- Reutilização via traits
- Padrões de projeto SOLID

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
