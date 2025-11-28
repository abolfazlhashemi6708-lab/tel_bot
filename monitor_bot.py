from flask import Flask, request
import telebot

TOKEN = "8496222681:AAFB9nJ0VXNlHeb2YzuoN9FcFozFSA07srQ"   # ← توکن جدید رباتت را اینجا بگذار
bot = telebot.TeleBot(TOKEN)

app = Flask(__name__)

# ─────────────────────────────────────────────
# 1) مسیر GET برای تست اجرا
# ─────────────────────────────────────────────
@app.route("/", methods=["GET"])
def index():
    return "Webhook is running!", 200

# ─────────────────────────────────────────────
# 2) مسیر POST برای دریافت پیام از تلگرام
# ─────────────────────────────────────────────
@app.route("/", methods=["POST"])
def webhook():
    json_str = request.get_data().decode("utf-8")
    update = telebot.types.Update.de_json(json_str)
    bot.process_new_updates([update])
    return "OK", 200

# ─────────────────────────────────────────────
# 3) هندلرهای ربات (دقیقاً مثل monitor_bot)
# ─────────────────────────────────────────────
@bot.message_handler(commands=['start'])
def start_cmd(message):
    bot.reply_to(message, "سلام! ربات روی هاست فعال شد ✔️")

@bot.message_handler(func=lambda msg: True)
def all_msgs(message):
    bot.reply_to(message, f"پیام دریافت شد: {message.text}")

# ──────────────── پایان ───────────────────────
if __name__ == "__main__":
    app.run()
