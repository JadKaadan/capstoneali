import java.util.ArrayList;

public class Client {
    private String name;
    private ArrayList<String> receipts;

    public Client(String name) {
        this.name = name;
        this.receipts = new ArrayList<>();
    }

    public String getName() {
        return name;
    }

    public void addReceipt(String receipt) {
        receipts.add(receipt);
    }

    public String getReceipts() {
        StringBuilder allReceipts = new StringBuilder("Receipts for " + name + ":\n");
        for (String receipt : receipts) {// receipt
            allReceipts.append(receipt).append("\n\n");
        }
        return allReceipts.toString();
    }
}
