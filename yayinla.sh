#!/bin/bash

MESSAGE=$1
VERSION=$2

if [ -z "$MESSAGE" ]; then
    echo "❌ Hata: Bir commit mesajı girmelisiniz!"
    echo "Kullanım (Otomatik Versiyon): ./yayinla.sh \"Commit mesajınız\""
    echo "Kullanım (Özel Versiyon)   : ./yayinla.sh \"Commit mesajınız\" \"1.0.3\""
    exit 1
fi

rm -f index.php~
if ! grep -q "^uploads/" .gitignore 2>/dev/null; then
    echo -e "\nuploads/" >> .gitignore
fi

if [ -z "$VERSION" ]; then
    LAST_TAG=$(git describe --tags --abbrev=0 2>/dev/null)
    if [ -z "$LAST_TAG" ]; then
        VERSION="1.0.1"
    else
        RAW_VER=${LAST_TAG#v}
        VERSION=$(echo $RAW_VER | awk -F. '{$NF = $NF + 1;} 1' OFS=.)
    fi
    echo "ℹ️  Versiyon belirtilmedi, otomatik hesaplandı: v$VERSION"
else
    VERSION=${VERSION#v}
fi

TAG_NAME="v$VERSION"

echo "--------------------------------------------------"
echo "📦 Güncelleme ve Versiyonlama Başlatılıyor..."
echo "📝 Mesaj   : $MESSAGE"
echo "🏷️  Versiyon: $TAG_NAME"
echo "--------------------------------------------------"

git add .
git commit -m "$MESSAGE"
git push origin main

git tag -a "$TAG_NAME" -m "$MESSAGE"
git push origin "$TAG_NAME"

echo ""
echo "🚀 $TAG_NAME etiketi GitHub'a başarıyla gönderildi!"
echo "✅ ZiraatBox başarıyla güncellendi ve versiyonlandı!"
