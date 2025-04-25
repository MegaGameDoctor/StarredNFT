package net.gamedoctor.NFTBot;

import java.sql.*;

public class MySQLDBManager {
    private final TGBot tgBot;
    private final String userDataTable = "userdata";
    private final String marketTable = "market";
    private Connection connection;

    public MySQLDBManager(TGBot tgBot) {
        this.tgBot = tgBot;
        Config cfg = tgBot.getCfg();
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            connection = DriverManager.getConnection("jdbc:mysql://" + cfg.getDatabase_host() + "/" + cfg.getDatabase_database() + cfg.getDatabase_arguments(), cfg.getDatabase_user(), cfg.getDatabase_password());
        } catch (Exception e) {
            e.printStackTrace();
        }

        //keepAlive();
        checkForMessages();
    }

    private void checkForMessages() {
        new Thread(() -> {
            while (true) {
                try {
                    Thread.sleep(2000L);
                } catch (InterruptedException e) {
                    throw new RuntimeException(e);
                }
                try {
                    ResultSet set = connection.prepareStatement("SELECT * FROM msgsForSend ORDER BY id").executeQuery();

                    while (set.next()) {
                        int id = set.getInt("id");
                        long target = set.getLong("target");
                        String message = set.getString("message");
                        String additional = set.getString("additional");
                        if (additional.startsWith("send_confirm")) {
                            tgBot.sendGiftSendConfirmation(message.replace("%buyer%", tgBot.getUsernameByID(Long.parseLong(additional.split(":")[1]))), target, Long.parseLong(additional.split(":")[1]));
                        } else if (additional.startsWith("buy_stars")) {
                            tgBot.sendInvoice(Long.parseLong(additional.split(":")[1]), Long.parseLong(additional.split(":")[1]), Integer.parseInt(additional.split(":")[2]));
                        } else {
                            tgBot.sendMessage(target, message);
                        }

                        PreparedStatement preparedStatement = connection.prepareStatement("DELETE FROM msgsForSend WHERE id = ?");
                        preparedStatement.setInt(1, id);
                        preparedStatement.executeUpdate();
                    }
                } catch (SQLException ignored) {
                }
            }
        }).start();
    }

    private void keepAlive() {
        new Thread(() -> {
            while (true) {
                try {
                    Thread.sleep(2000L);
                } catch (InterruptedException e) {
                    throw new RuntimeException(e);
                }
                try {
                    connection.prepareStatement("SET NAMES utf8").execute();
                } catch (SQLException ignored) {
                }
            }
        }).start();
    }

    public void changeActiveOfferStatus(long buyer, long seller, String status) {
        new Thread(() -> {
            try {
                PreparedStatement preparedStatement = connection.prepareStatement("UPDATE active_offers SET status = ? WHERE buyer = ? AND seller = ?");
                preparedStatement.setString(1, status);
                preparedStatement.setLong(2, buyer);
                preparedStatement.setLong(3, seller);
                preparedStatement.executeUpdate();
            } catch (SQLException e) {
                e.printStackTrace();
            }
        }).start();
    }

    public int closeOffer(long buyer, long seller) {
        int sum = 0;
        try {
            PreparedStatement preparedStatement = connection.prepareStatement("DELETE FROM active_offers WHERE buyer = ? AND seller = ?");
            preparedStatement.setLong(1, buyer);
            preparedStatement.setLong(2, seller);
            preparedStatement.executeUpdate();

            preparedStatement = connection.prepareStatement("SELECT * FROM " + marketTable + " WHERE owner = ? AND status = ?");
            preparedStatement.setLong(1, seller);
            preparedStatement.setString(2, "SELLING");
            ResultSet set = preparedStatement.executeQuery();

            if (set.next()) {
                preparedStatement = connection.prepareStatement("UPDATE " + marketTable + " SET status = ? WHERE id = ?");
                preparedStatement.setString(1, "SOLD");
                preparedStatement.setInt(2, set.getInt("id"));
                preparedStatement.executeUpdate();

                addBalance(seller, set.getInt("price"));
                sum = set.getInt("price");
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }

        return sum;
    }

    public boolean isUserExists(long userID) {
        try {
            PreparedStatement preparedStatement = connection.prepareStatement("SELECT COUNT(*) FROM " + userDataTable + " WHERE tg_id = ?");
            preparedStatement.setLong(1, userID);
            ResultSet set = preparedStatement.executeQuery();

            if (set.next() && set.getInt("COUNT(*)") > 0) {
                return true;
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }

        return false;
    }

    public int getBalance(long userID) {
        try {
            PreparedStatement preparedStatement = connection.prepareStatement("SELECT * FROM " + userDataTable + " WHERE tg_id = ?");
            preparedStatement.setLong(1, userID);
            ResultSet set = preparedStatement.executeQuery();

            if (set.next()) {
                return set.getInt("balance");
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }

        return 0;
    }

    public void addBalance(long userID, int toAdd) {
        new Thread(() -> {
            try {
                PreparedStatement preparedStatement = connection.prepareStatement("UPDATE " + userDataTable + " SET balance = balance + ? WHERE tg_id = ?");
                preparedStatement.setInt(1, toAdd);
                preparedStatement.setLong(2, userID);
                preparedStatement.executeUpdate();
            } catch (SQLException e) {
                e.printStackTrace();
            }
        }).start();
    }

    public void removeBalance(long userID, int toRemove) {
        new Thread(() -> {
            try {
                PreparedStatement preparedStatement = connection.prepareStatement("UPDATE " + userDataTable + " SET balance = balance - ? WHERE tg_id = ?");
                preparedStatement.setInt(1, toRemove);
                preparedStatement.setLong(2, userID);
                preparedStatement.executeUpdate();
            } catch (SQLException e) {
                e.printStackTrace();
            }
        }).start();
    }
}