<?
import requests
from bs4 import BeautifulSoup
import telebot
import time
import hashlib

# =====================
# تنظیمات ربات تلگرام
# =====================
BOT_TOKEN = "8496222681:AAFB9nJ0VXNlHeb2YzuoN9FcFozFSA07srQ"
CHAT_ID = "160863054"
bot = telebot.TeleBot(BOT_TOKEN)

# =====================
# لینک صفحه وب که باید بررسی شود
# =====================
URL = "https://www.misaghegg.com/cart/"   # اینجا لینک صفحه‌ای که می‌خواهی چک شود را بگذار
CHECK_INTERVAL = 60           # بررسی هر ۶۰ ثانیه

last_hash = None
last_text = ""

def get_page_content():
    try:
        response = requests.get(URL)
        response.raise_for_status()
        soup = BeautifulSoup(response.text, "html.parser")

        # کل متن صفحه بررسی شود
        text = soup.get_text(separator="\n")
        return text.strip()

    except Exception as e:
        print("خطا:", e)
        return None


def check_changes():
    global last_hash, last_text

    content = get_page_content()
    if content is None:
        return

    current_hash = hashlib.md5(content.encode()).hexdigest()

    # در اولین اجرا
    if last_hash is None:
        last_hash = current_hash
        last_text = content
        print("ربات شروع به مانیتورینگ کرد...")
        bot.send_message(CHAT_ID, "ربات فعال شد و صفحه را زیر نظر دارد.")
        return

    # تشخیص تغییر
    if current_hash != last_hash:
        print("تغییر شناسایی شد!")
        bot.send_message(CHAT_ID, "⚠️ تغییر جدید در صفحه وب شناسایی شد!")

        # فقط خطوط تغییر کرده را ارسال کن
        old_lines = last_text.splitlines()
        new_lines = content.splitlines()
        changes = []

        for old, new in zip(old_lines, new_lines):
            if old != new:
                changes.append(f"- قدیم: {old}\n+ جدید: {new}")

        if not changes:
            changes.append("تغییرات جدیدی ایجاد شده ولی خطوط دقیق قابل تشخیص نبود.")

        change_msg = "\n\n".join(changes[:10])  # فقط ۱۰ خط برای جلوگیری از طولانی شدن
        bot.send_message(CHAT_ID, change_msg)

        # بروزرسانی هش
        last_hash = current_hash
        last_text = content
    else:
        print("بدون تغییر.")

# =====================
# حلقه اصلی
# =====================
while True:
    check_changes()
    time.sleep(CHECK_INTERVAL)
?>

