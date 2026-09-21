#!/bin/bash

MESSAGE=$1
VERSION=$2

# 1. Message kontrolü
if [ -z "$MESSAGE" ]; then
    echo "❌ Hata: Bir commit mesajı girmelisiniz!"
    echo "Kullanım (Otomatik Versiyon): ./yayinla.sh \"Commit mesajınız\""
    echo "Kullanım (Özel Versiyon)   : ./yayinla.sh \"Commit mesajınız\" \"1.0.3\""
    exit 1
fi

# 2. Geçici dosyaları temizle ve .gitignore kontrolü yap
rm -f public_html/index.php~
if ! grep -q "public_html/uploads/" .gitignore 2>/dev/null; then
    echo -e "\npublic_html/uploads/" >> .gitignore
fi

# 3. Versiyon Numarasını Belirle
if [ -z "$VERSION" ]; then
    # GitHub/Git üzerindeki en son etiketi bul (örn: v1.0.1)
    LAST_TAG=$(git describe --tags --abbrev=0 2>/dev/null)
    if [ -z "$LAST_TAG" ]; then
        VERSION="1.0.1"
    else
        # Son sayısal kısmı 1 artır (1.0.1 -> 1.0.2)
        RAW_VER=${LAST_TAG#v}
        VERSION=$(echo $RAW_VER | awk -F. '{$NF = $NF + 1;} 1' OFS=.)
    fi
    echo "ℹ️  Versiyon belirtilmedi, otomatik hesaplandı: v$VERSION"
else
    # Kullanıcı elle versiyon girdiyse 'v' harfi yazdıysa temizle
    VERSION=${VERSION#v}
fi

TAG_NAME="v$VERSION"

echo "--------------------------------------------------"
echo "📦 Güncelleme ve Versiyonlama Başlatılıyor..."
echo "📝 Mesaj   : $MESSAGE"
echo "🏷️  Versiyon: $TAG_NAME"
echo "--------------------------------------------------"

# 4. Git İşlemleri
git add .
git commit -m "$MESSAGE"
git push origin main

# 5. Tag/Release Oluştur ve Fırlat
git tag -a "$TAG_NAME" -m "$MESSAGE"
git push origin "$TAG_NAME"

echo ""
echo "🚀 $TAG_NAME etiketi GitHub'a başarıyla gönderildi!"
echo "✅ ZiraatBox başarıyla güncellendi ve versiyonlandı!"
