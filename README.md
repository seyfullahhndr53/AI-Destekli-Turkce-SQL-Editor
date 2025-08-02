🚀 Modern SQL Editor
![Modern SQL Editor Demo](assets/demo.gif)
Modern, kullanıcı dostu ve yapay zeka destekli bir SQL öğrenme platformu. Gerçek hayattan alınmış Türkçe verilerle dolu bir veritabanı üzerinde SQL öğrenmenin en kolay ve en interaktif yolu!

✨ Öne Çıkan Özellikler
🎯 Temel Özellikler
Modern ve Duyarlı Arayüz:
Bootstrap tabanlı, tüm cihazlarda harika görünen modern bir tasarım.

Türkçe Veritabanı:
Gerçek Türkçe isimler, şehirler ve ürünlerle dolu, anlamlı veriler üzerinde çalışma imkanı.

Canlı SQL Editörü:
Syntax highlighting (sözdizimi renklendirme) özellikli, anında sorgu çalıştırma imkanı sunan interaktif editör.

Anında Sonuçlar:
Yazdığınız SQL sorgularını anında çalıştırın ve sonuçları anlık olarak tablo formatında görüntüleyin.

Tema Desteği:
Göz yormayan "Dark Mode" (Karanlık Mod) ve aydınlık tema seçenekleri.

🤖 Yapay Zeka Destekli Yetenekler
Akıllı Soru Analizi:
Doğal dilde yazdığınız soruları analiz ederek size uygun SQL sorguları önerir.

Ollama Entegrasyonu:
Lokalde çalışan güçlü yapay zeka modeli "OpenHermes" ile veritabanı şemasını analiz eder ve en optimize sorguyu üretir.

Tablo ve Kolon Önerileri:
Sorduğunuz soruya en uygun tabloları ve kullanmanız gereken kolonları size sunar, böylece veritabanını keşfetmeniz kolaylaşır.

📊 Veritabanı Yönetimi
Rastgele Veri Üretimi:
Tek tıkla binlerce satırlık rastgele ve anlamlı test verisi oluşturun.

Kolay Sıfırlama:
Veritabanını istediğiniz zaman orijinal, temiz haline döndürün.

Veri Dışa Aktarma:
Sorgu sonuçlarını CSV formatında kolayca bilgisayarınıza indirin.

🚀 Kurulum (XAMPP ile Çok Kolay!)
Bu proje, özellikle XAMPP gibi yerel sunucu ortamlarında kolayca çalıştırılmak üzere tasarlanmıştır. Kurulum için aşağıdaki adımları izleyebilirsiniz.

Gereksinimler
XAMPP:
İçerisinde Apache ve PHP bulunan güncel bir XAMPP sürümü.
(Buradan İndirin)

pdo_sqlite Eklentisi:
XAMPP kurulumuyla birlikte genellikle aktif olarak gelir.

Adım Adım Kurulum
XAMPP'u Kurun ve Başlatın:

Eğer bilgisayarınızda yüklü değilse, yukarıdaki linkten XAMPP'u indirin ve kurun.

XAMPP Kontrol Panelini açın ve Apache modülünü Start butonuna basarak çalıştırın.

bash
Kopyala
Düzenle
# XAMPP kontrol panelinde Apache modülünü başlatın
Proje Dosyalarını İndirin:

Bu GitHub sayfasının sağ üst köşesindeki Code butonuna tıklayın ve Download ZIP seçeneği ile proje dosyalarını bilgisayarınıza indirin.

İndirdiğiniz .zip dosyasını bir klasöre çıkartın. Klasörün ismini daha basit bir hale (örneğin sql-editor) getirebilirsiniz.

Dosyaları htdocs Klasörüne Taşıyın:

Proje dosyalarını çıkarttığınız klasörü kopyalayın.

XAMPP'un kurulu olduğu dizindeki htdocs klasörünün içine yapıştırın.
(Genellikle C:\xampp\htdocs\ konumundadır).

bash
Kopyala
Düzenle
# Örnek yol
C:\xampp\htdocs\sql-editor
Tarayıcıda Açın:

Web tarayıcınızı açın ve adres çubuğuna şu adresi yazın (klasör adını kendi verdiğiniz isimle değiştirin):

arduino
Kopyala
Düzenle
http://localhost/sql-editor
Karşınıza gelen ekrandaki talimatları izleyerek veritabanını oluşturun. Artık editörü kullanmaya hazırsınız!

Yapay Zeka Özellikleri İçin (Opsiyonel)
Yapay zeka destekli soru analizini kullanmak için yerel makinenize Ollama'yı kurmanız gerekmektedir.

Ollama'yı indirin ve kurun:
resmi sitesinden indirebilirsiniz

OpenHermes modelini indirin:

bash
Kopyala
Düzenle
ollama pull openhermes
Ollama servisini başlatın:

bash
Kopyala
Düzenle
ollama serve
🎮 Kullanım
Temel SQL Sorguları
SQL Editörüne sorgunuzu yazın.

"Sorguyu Çalıştır" butonuna tıklayın veya Ctrl+Enter basın.

Sonuçları anında alt kısımdaki panelde görün.

-- Örnek sorgular:

sql
Kopyala
Düzenle
SELECT * FROM Musteriler LIMIT 5;
SELECT UrunAdi, Fiyat FROM Urunler ORDER BY Fiyat DESC LIMIT 10;
SELECT Sehir, COUNT(*) as MusteriSayisi FROM Musteriler GROUP BY Sehir;
AI Destekli Soru Analizi
"Soru Analizi" bölümüne doğal dilde sorunuzu yazın.

"Soruyu Analiz Et" butonuna tıklayın.

Yapay zeka size önerilen tabloları, hazır SQL sorgusunu ve bir açıklama sunacaktır.

Örnek Sorular:

"En pahalı 5 ürün hangileri?"

"Ankara'daki müşterilerim kimler?"

“Müşteriler tablosundaki şehir bilgisi 'Rize' olan kayıtları listele.”

🏗️ Proje Yapısı ve Geliştirici Notları
Bu proje, öğrenme ve deneme amaçlı bir araç olarak geliştirilmiştir. Kod tabanı, modern PHP ve JavaScript pratiklerini benimsemeyi hedefler.

Backend:
Proje, temel bir MVC (Model-View-Controller) mimarisine sahiptir ve PHP ile yazılmıştır.
Veritabanı işlemleri için hafif ve hızlı olan SQLite kullanılmaktadır.

Frontend:
Arayüzün ana mantığı modern-app.js içerisinde modern vanilla JavaScript ile yazılmıştır.
Bootstrap ve jQuery gibi kütüphaneler, arayüz bileşenleri ve eski tarayıcı uyumluluğu için projede yer almaktadır.

Güvenlik Notu:
Bu proje bir öğrenme aracı olarak tasarlandığı için güvenlik önlemleri üretim seviyesinde değildir. Yerel ortamda (localhost) kullanılması hedeflenmiştir.
Eğer bu projeyi halka açık bir sunucuda yayınlamayı düşünüyorsanız, SQL enjeksiyonu gibi zafiyetlere karşı tüm sorguları PHP PDO prepared statements kullanarak yeniden yapılandırmanız şiddetle tavsiye edilir.

🙏 Teşekkürler
Eren Hatırnaz: Projenin orijinal geliştiricisi ve fikir babası.

Seyfullah Hanedar: Modern arayüz ve yapay zeka entegrasyonlarını geliştiren katkı sahibi.

Hüseyin Demirtaş: Projede kullanılan zengin Türkçe isim ve soyisim listesi için.

Claude AI: Kod optimizasyonu, dokümantasyon ve yapay zeka entegrasyonu konularında sağladığı destek için.

Ollama, Bootstrap ve SQLite Ekipleri: Sağladıkları açık kaynaklı araçlar için.

Bu proje, Eren Hatırnaz tarafından geliştirilen w3c-sql-editor-with-turkish-database reposu temel alınarak türetilmiş ve üzerine modern arayüz ile yapay zeka yetenekleri eklenmiştir.

Not:
Türkçe ad-soyad verileri, Hüseyin Demirtaş’ın bu paylaşımından alınmıştır.
Bu veriler yalnızca eğitim ve test amacıyla kullanılmakta olup, ticari bir amaç güdülmemektedir.
İlgili içerik sahibinin telif haklarına saygı gösterilmektedir. Talep hâlinde veri kaldırılabilir.

⭐ Projeyi beğendiyseniz GitHub'da yıldız vermeyi unutmayın!
