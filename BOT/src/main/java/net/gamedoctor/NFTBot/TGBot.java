package net.gamedoctor.NFTBot;

import lombok.Getter;
import org.telegram.telegrambots.client.okhttp.OkHttpTelegramClient;
import org.telegram.telegrambots.longpolling.TelegramBotsLongPollingApplication;
import org.telegram.telegrambots.longpolling.util.LongPollingSingleThreadUpdateConsumer;
import org.telegram.telegrambots.meta.api.methods.AnswerPreCheckoutQuery;
import org.telegram.telegrambots.meta.api.methods.groupadministration.GetChat;
import org.telegram.telegrambots.meta.api.methods.invoices.SendInvoice;
import org.telegram.telegrambots.meta.api.methods.send.SendMessage;
import org.telegram.telegrambots.meta.api.methods.updatingmessages.DeleteMessage;
import org.telegram.telegrambots.meta.api.objects.Update;
import org.telegram.telegrambots.meta.api.objects.chat.Chat;
import org.telegram.telegrambots.meta.api.objects.payments.LabeledPrice;
import org.telegram.telegrambots.meta.api.objects.payments.PreCheckoutQuery;
import org.telegram.telegrambots.meta.api.objects.payments.SuccessfulPayment;
import org.telegram.telegrambots.meta.api.objects.replykeyboard.InlineKeyboardMarkup;
import org.telegram.telegrambots.meta.api.objects.replykeyboard.ReplyKeyboardRemove;
import org.telegram.telegrambots.meta.api.objects.replykeyboard.buttons.InlineKeyboardButton;
import org.telegram.telegrambots.meta.api.objects.replykeyboard.buttons.InlineKeyboardRow;
import org.telegram.telegrambots.meta.exceptions.TelegramApiException;
import org.telegram.telegrambots.meta.generics.TelegramClient;

import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

public class TGBot implements LongPollingSingleThreadUpdateConsumer {
    private final TelegramClient telegramClient;
    @Getter
    private final Config cfg;
    @Getter
    private final MySQLDBManager mySQLDBManager;

    public TGBot() {
        cfg = new Config();
        telegramClient = new OkHttpTelegramClient(cfg.getBotToken());
        mySQLDBManager = new MySQLDBManager(this);
        try {
            TelegramBotsLongPollingApplication botsApplication = new TelegramBotsLongPollingApplication();
            botsApplication.registerBot(cfg.getBotToken(), this);
            System.out.println("Бот успешно запущен!");
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void sendWelcomeMessage(long chatId) {
        InlineKeyboardButton miniAppButton = new InlineKeyboardButton("Открыть приложение");
        miniAppButton.setUrl("https://t.me/starrednft_bot/app");

        List<InlineKeyboardRow> rows = new ArrayList<>();
        rows.add(new InlineKeyboardRow(miniAppButton));
        InlineKeyboardMarkup keyboard = new InlineKeyboardMarkup(rows);

        SendMessage message = new SendMessage(String.valueOf(chatId), "Добро пожаловать на первый НФТ маркет за Звёзды в Телеграмм!\n\nКоманды:\n/balance - Ваш текущий баланс");
        message.setReplyMarkup(keyboard);

        try {
            telegramClient.execute(message);
        } catch (TelegramApiException e) {
            e.printStackTrace();
        }
    }

    private boolean checkUserExists(long userID, long chatId) {
        if (!mySQLDBManager.isUserExists(userID)) {
            sendMessage(chatId, "Для использования этой команды Вы должны быть зарегистрированы. Запустите приложение и попробуйте снова");
            return false;
        }

        return true;
    }

    public void sendInvoice(long userID, long chatId, int amount) {
        try {
            telegramClient.execute(SendInvoice.builder()
                    .chatId(chatId)
                    .title("Пополнение баланса")
                    .description("Вы пополняете баланс на " + amount + " звёзд.")
                    .payload(userID + "-" + UUID.randomUUID())
                    .currency("XTR")
                    .providerToken("")
                    .prices(List.of(new LabeledPrice("Пополнение баланса", amount))) // 100 Stars
                    .build());
        } catch (TelegramApiException e) {
            e.printStackTrace();
        }
    }

    private void handlePreCheckoutQuery(PreCheckoutQuery preCheckoutQuery) {
        try {
            telegramClient.execute(new AnswerPreCheckoutQuery(preCheckoutQuery.getId(), true));
        } catch (TelegramApiException e) {
            e.printStackTrace();
        }
    }

    private void handleSuccessfulPayment(long chatId, SuccessfulPayment successfulPayment) {
        int amount = successfulPayment.getTotalAmount();
        String[] data = successfulPayment.getInvoicePayload().split("-");
        long userID = Long.parseLong(data[0]);
        mySQLDBManager.addBalance(userID, amount);
        sendMessage(chatId, "Успех! На ваш баланс зачислено " + amount + " ⭐");
        System.out.println("Пополнение баланса на " + amount + " ⭐, пользователь " + chatId + ". Транзакция: " + successfulPayment.getInvoicePayload());
    }

    public void sendMessage(long chatId, String text) {
        SendMessage message = new SendMessage(String.valueOf(chatId), text);
        message.setReplyMarkup(new ReplyKeyboardRemove(true));

        try {
            telegramClient.execute(message);
        } catch (TelegramApiException e) {
            e.printStackTrace();
        }
    }

    public void sendGiftSendConfirmation(String text, long userID, long target) {
        SendMessage message = new SendMessage(String.valueOf(userID), text);

        InlineKeyboardButton button = new InlineKeyboardButton("Я передал подарок");
        button.setCallbackData("giftSendConfirmation_" + userID + "_" + target);

        List<InlineKeyboardRow> rows = new ArrayList<>();
        rows.add(new InlineKeyboardRow(button));
        InlineKeyboardMarkup keyboard = new InlineKeyboardMarkup(rows);

        message.setReplyMarkup(keyboard);

        try {
            telegramClient.execute(message);
        } catch (TelegramApiException e) {
            e.printStackTrace();
        }
    }

    public void sendGiftReceivedConfirmation(String text, long userID, long target) {
        SendMessage message = new SendMessage(String.valueOf(userID), text);

        InlineKeyboardButton button = new InlineKeyboardButton("Я получил подарок");
        button.setCallbackData("giftReceivedConfirmation_" + userID + "_" + target);

        List<InlineKeyboardRow> rows = new ArrayList<>();
        rows.add(new InlineKeyboardRow(button));
        InlineKeyboardMarkup keyboard = new InlineKeyboardMarkup(rows);

        message.setReplyMarkup(keyboard);

        try {
            telegramClient.execute(message);
        } catch (TelegramApiException e) {
            e.printStackTrace();
        }
    }

    @Override
    public void consume(Update update) {
        if (update.hasCallbackQuery()) {
            String chatId = update.getCallbackQuery().getMessage().getChatId().toString();
            String callbackData = update.getCallbackQuery().getData();
            if (callbackData.startsWith("giftSendConfirmation_")) {
                DeleteMessage deleteMessage = new DeleteMessage(chatId, update.getCallbackQuery().getMessage().getMessageId());

                try {
                    telegramClient.execute(deleteMessage);
                } catch (TelegramApiException e) {
                    e.printStackTrace();
                }

                long seller = Long.parseLong(callbackData.split("_")[1]);
                long buyer = Long.parseLong(callbackData.split("_")[2]);

                sendMessage(seller, "Вы подтвердили отправку. Средства будут зачислены, как только покупатель подтвердит получение.");

                mySQLDBManager.changeActiveOfferStatus(buyer, seller, "WAITING_FOR_BUYER");

                sendGiftReceivedConfirmation("Продавец подтвердил отправку товара. Подтвердите его получение, нажав на кнопку ниже", buyer, seller);
            } else if (callbackData.startsWith("giftReceivedConfirmation_")) {
                long seller = Long.parseLong(callbackData.split("_")[2]);
                long buyer = Long.parseLong(callbackData.split("_")[1]);

                DeleteMessage deleteMessage = new DeleteMessage(chatId, update.getCallbackQuery().getMessage().getMessageId());

                try {
                    telegramClient.execute(deleteMessage);
                } catch (TelegramApiException e) {
                    e.printStackTrace();
                }
                new Thread(() -> {
                    sendMessage(seller, "Покупатель подтведил получение подарка. На ваш баланс зачислено " + mySQLDBManager.closeOffer(buyer, seller) + " ⭐");
                    sendMessage(buyer, "Сделка завершена. Спасибо за пользование нашим маркетом!");
                }).start();
            }
        }
        if (update.hasMessage() && update.getMessage().hasText()) {
            String command = update.getMessage().getText();
            long chatId = update.getMessage().getChatId();
            long userID = update.getMessage().getFrom().getId();

            switch (command.toLowerCase().split(" ")[0]) {
                case "/balance":
                    new Thread(() -> {
                        sendMessage(chatId, "Ваш баланс: " + mySQLDBManager.getBalance(userID) + " ⭐\n\nПополнить баланс Вы можете в приложении.");
                    }).start();
                    break;
                default:
                    sendWelcomeMessage(chatId);
            }
        } else if (update.hasPreCheckoutQuery()) {
            handlePreCheckoutQuery(update.getPreCheckoutQuery());
        } else if (update.hasMessage() && update.getMessage().hasSuccessfulPayment()) {
            handleSuccessfulPayment(update.getMessage().getChatId(), update.getMessage().getSuccessfulPayment());
        }
    }

    public String getUsernameByID(Long userId) {
        try {
            GetChat getChat = new GetChat(userId.toString());
            Chat chat = telegramClient.execute(getChat);
            return "@" + chat.getUserName();
        } catch (TelegramApiException e) {
            return userId.toString();
        }
    }
}