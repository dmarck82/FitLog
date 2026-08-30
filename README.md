# Diário Fitness

MVP pessoal para acompanhar medidas corporais, medicamentos, alimentação, exames e treinos planejados versus realizados.

## Funcionalidades

- Pesagens, composição e medidas corporais
- Medicamentos e suplementos, incluindo compras, aplicações, consumos e feedback
- Planos alimentares e comparação entre refeições planejadas e realizadas
- Planos de treino por período e dia da semana
- Catálogo pessoal de exercícios
- Registro de séries, repetições, carga, duração e distância
- Comparação entre treino planejado e realizado, com aderência e volume
- Histórico preservado por snapshots mesmo após alterações no planejamento

## Requisitos

- PHP 8.2 ou superior com `pdo_mysql`
- MySQL 8 ou MariaDB compatível
- Navegador moderno

Não há framework nem dependências gerenciadas por Composer ou npm. A interface usa Bootstrap 5, Bootstrap Icons e jQuery 3.7.1 por CDN.

## Instalação

1. Copie `.env.exemplo` para `.env` e ajuste a conexão com o MySQL.
2. Crie o banco configurado em `DB_BANCO`:

```sql
CREATE DATABASE diario_fitness
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

3. Execute as migrations:

```bash
php scripts/executar_migrations.php
```

4. Crie o primeiro usuário. A senha será solicitada sem aparecer na tela:

```bash
php scripts/criar_usuario.php "Douglas" "seu-email@exemplo.com"
```

5. Inicie o servidor local:

```bash
php -S localhost:8080 -t public public/roteador.php
```

Acesse `http://localhost:8080`.

## Testes

Os testes são scripts PHP independentes:

```bash
php tests/teste_funcoes.php
php tests/teste_objetivos.php
php tests/teste_suplementos.php
php tests/teste_alimentacao.php
php tests/teste_treinos.php
```

## Estrutura

```text
app/                    inicialização, funções e controladores
database/migrations/    estrutura versionada do MySQL
public/                 único diretório exposto pelo servidor web
scripts/                instalação e manutenção via terminal
storage/                documentos privados e logs
templates/              páginas e componentes visuais
tests/                  testes simples sem framework
```

As tabelas usam o padrão `<prefixo>_<entidade>`, sempre em português e no singular. Exemplo: `usu_usuario` e `mec_medicao_corporal`.

