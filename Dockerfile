# Usa a imagem oficial do PHP de terminal (muito leve)
FROM php:8.2-cli

# Instala a extensão do PDO para o PHP conseguir falar com o MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Define a pasta de trabalho dentro do contêiner
WORKDIR /app

# Copia os arquivos do Windows para dentro do contêiner
COPY . .

# O comando que o contêiner vai rodar ao ligar
CMD ["php", "daemon.php"]