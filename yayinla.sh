#!/bin/bash

MESSAGE=$1
VERSION=$2

if [ -z "$MESSAGE" ]; then
    echo "❌ Hata: Bir commit mesajı girmelisiniz!"
    echo "Kullanım: ./yayinla.sh \"Düzeltme mesajı\" \"1.0.1\""
    exit 1
fi

# Geçici dosyaları ve yüklemeleri temizle/hariç tut
rm -f public_html/index.php~
if ! grep -q "public_html/uploads/" .gitignore 2>/dev/null; then
    echo -e "\npublic_html/uploads/" >> .gitignore
fi

# Git İşlemleri
git add .
git commit -m "$MESSAGE"
git push origin main

# Versiyon girildiyse Tag/Release oluştur
if [ -n "$VERSION" ]; then
    git tag -a "v$VERSION" -m "$MESSAGE"
    git push origin "v$VERSION"
    echo "🚀 v$VERSION etiketi GitHub'a başarıyla gönderildi!"
fi

echo "✅ Ziraatbox başarıyla GitHub'a güncellendi!"
