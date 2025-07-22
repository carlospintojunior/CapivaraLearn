#!/bin/bash
# Script para remover scripts de diagnóstico e arquivos temporários
rm -f crud/test_path.php
rm -f crud/test_no_logger.php
# Remover outros arquivos de diagnóstico, se existirem
find crud -type f -name 'test_*.php' -delete
find includes -type f -name '*.bak' -delete
find . -type f -name '*~' -delete

echo "Diagnósticos e arquivos temporários removidos."
