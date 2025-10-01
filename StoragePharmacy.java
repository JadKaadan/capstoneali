
/*Manages the inventory of drugs, sold items, and client information. 
It includes methods for adding drugs, updating drug information, selling items, and managing client data. */
import java.util.ArrayList;
import javax.swing.JComboBox;
import javax.swing.JOptionPane;
import javax.swing.JPanel;

public class StoragePharmacy {
    // Lists to keep track of items and sold items
    ArrayList<item> itemList = new ArrayList<>();
    ArrayList<item> soldItems = new ArrayList<>();
    ArrayList<Client> clientList = new ArrayList<>();
    // Constant for the VAT rate
    final double VAT_RATE = 0.20;

    // Constructor to populate itemList with predefined items
    public StoragePharmacy() {
        itemList.addAll(item.getPredefinedItems());
        System.out.println("Total items in inventory after initialization: " + itemList.size());
    }

    // Method to add an item to the inventory
    public void addItem(item drugProduct) {
        // Check if an item with the same name already exists
        boolean itemExists = false;
        for (item existingItem : itemList) {
            if (existingItem.hasSameName(drugProduct.itemName)) {
                itemExists = true;
                break;
            }
        }
        if (!itemExists) {
            for (item existingItem : item.predefinedItems) {
                if (existingItem.hasSameName(drugProduct.itemName)) {
                    itemExists = true;
                    break;
                }
            }
        }

        if (itemExists) {
            JOptionPane.showMessageDialog(null, "Item with name '" + drugProduct.itemName + "' already exists.");
            return;
        }

        itemList.add(drugProduct);
        JOptionPane.showMessageDialog(null, "Drug product has just been added to the inventory.");
    }

    // Method to retrieve item names
    public String[] getItemNames() {
        String[] itemNames = new String[itemList.size()];
        for (int i = 0; i < itemList.size(); i++) {
            itemNames[i] = itemList.get(i).getItemName();
        }
        return itemNames;
    }

    // Method to update the price of a specific drug
    public void updatePrice(String drugName, double newP) {
        drugName = drugName.toLowerCase(); // Convert to lowercase
        for (int i = 0; i < itemList.size(); i++) {
            if (itemList.get(i).hasSameName(drugName)) {
                itemList.get(i).updateP(newP);
                JOptionPane.showMessageDialog(null, "Price updated for " + drugName + ": $" + newP);
                return;
            }
        }
        JOptionPane.showMessageDialog(null, "Item not found in inventory.");
    }

    // Method to update the quantity of a specific drug
    public void updateQuantity(String drugName, int newQuantity) {
        drugName = drugName.toLowerCase(); // Convert to lowercase
        for (int i = 0; i < itemList.size(); i++) {
            if (itemList.get(i).hasSameName(drugName)) {
                itemList.get(i).updateQuan(newQuantity);
                JOptionPane.showMessageDialog(null, "Quantity updated for " + drugName + ": " + newQuantity);
                return;
            }
        }
        JOptionPane.showMessageDialog(null, "Item not found in inventory.");
    }

    // Method to display sold items
    public void displaySoldItems() {
        StringBuilder message = new StringBuilder("\nSold Items:\n");
        for (item soldItem : soldItems) {
            message.append(soldItem.toString()).append("\n");
        }
        JOptionPane.showMessageDialog(null, message.toString());
    }

    public void referToDoctor(String customerSentence) {
        // Ask for the client's name
        String clientName = JOptionPane.showInputDialog(null, "Enter your name:");
        Client client = getClient(clientName);
        if (client == null) {
            // If the client does not exist, add them
            addClient(clientName);
            client = getClient(clientName); // Retrieve the newly added client
        }
        customerSentence = customerSentence.toLowerCase();

        // Split the sentence into words
        String[] words = customerSentence.split(
                "\\s+|\\bi\\b|\\bhave\\b|\\bthe\\b|\\band\\b|\\ba\\b|\\bor\\b|\\bis\\b|\\bit\\b|\\bof\\b|\\bto\\b|\\bin\\b|\\bfor\\b|\\bon\\b|\\bwith\\b|\\bas\\b|\\bbut\\b|\\bi\\b|\\byou\\b|\\bhe\\b|\\bshe\\b|\\bthis\\b|\\bthat\\b|\\bhello\\b|\\bdr\\b|\\b,\\b");

        ArrayList<item> relevantItems = new ArrayList<>();
        for (item product : itemList) {
            for (String word : words) {
                if (product.getDescription().toLowerCase().contains(word)) {
                    relevantItems.add(product);
                    break; // Break after finding the first relevant word
                }
            }
        }

        if (relevantItems.isEmpty()) {
            JOptionPane.showMessageDialog(null, "No relevant drug products found based on your sentence.");
            return;
        }

        // Create an array of drug product names for the JComboBox
        String[] itemNames = new String[relevantItems.size()];
        for (int i = 0; i < relevantItems.size(); i++) {
            itemNames[i] = relevantItems.get(i).getItemName();
        }

        double totalAmount = 0;
        double totalDiscount = 0;
        boolean buyingMore = true;
        StringBuilder receipt = new StringBuilder("Your purchase:\n");

        while (buyingMore) {
            // Create a JComboBox to display the list of drug products
            JComboBox<String> comboBox = new JComboBox<>(itemNames);
            JPanel panel = new JPanel();
            panel.add(comboBox);

            // Show the JComboBox to the user and get their selection
            JOptionPane.showMessageDialog(null, panel, "Choose a Drug Product", JOptionPane.QUESTION_MESSAGE);
            String selectedProductName = (String) comboBox.getSelectedItem();

            // Find the selected drug product in the list
            item selectedProduct = null;
            for (item drugProduct : relevantItems) {
                if (drugProduct.getItemName().equals(selectedProductName)) {
                    selectedProduct = drugProduct;
                    break;
                }
            }

            if (selectedProduct != null) {
                String quantityStr = JOptionPane.showInputDialog(null, "Enter the quantity:");
                int quantity = Integer.parseInt(quantityStr);

                // Check if enough quantity is available
                if (quantity > selectedProduct.getQuantity()) {
                    JOptionPane.showMessageDialog(null, "Not enough " + selectedProductName
                            + " in stock. Available quantity: " + selectedProduct.getQuantity());
                    continue; // Skip this iteration and let the user choose again
                }

                double totalPrice = selectedProduct.getPrice() * quantity;

                // Ask the user if they want to apply a discount
                int discountChoice = JOptionPane.showConfirmDialog(null, "Do you want to apply a discount?",
                        "Discount Confirmation", JOptionPane.YES_NO_OPTION);

                if (discountChoice == JOptionPane.YES_OPTION) {
                    String codeoutput = JOptionPane.showInputDialog("Enter code: ");
                    if (codeoutput.equalsIgnoreCase("yey code")) {
                        // If yes, ask for the discount percentage
                        String discountPercentageStr = JOptionPane.showInputDialog(null,
                                "Enter the discount percentage:");
                        double discountPercentage = Double.parseDouble(discountPercentageStr);

                        // Calculate the discounted price
                        double discountAmount = totalPrice * discountPercentage / 100;
                        totalPrice -= discountAmount;
                        totalDiscount += discountAmount; // Add the discount amount to the total discount
                    }
                }

                selectedProduct.updateQuan(selectedProduct.getQuantity() - quantity);

                // Update total amount and quantity
                totalAmount += totalPrice;

                // Append item details to the receipt
                receipt.append(selectedProduct.getItemName())
                        .append(" - Quantity: ").append(quantity)
                        .append(", Price per item: $").append(String.format("%.2f", selectedProduct.getPrice()))
                        .append(", Total Price: $").append(String.format("%.2f", totalPrice)).append("\n");
            }

            // Ask if they want to purchase additional items
            int response = JOptionPane.showConfirmDialog(null, "Do you want to purchase anything else?",
                    "Continue Shopping", JOptionPane.YES_NO_OPTION);
            buyingMore = (response == JOptionPane.YES_OPTION);
        }

        // Include total discount in the receipt if any
        if (totalDiscount > 0) {
            receipt.append("\nTotal discount: $").append(String.format("%.2f", totalDiscount));
        }

        // Calculate VAT on total amount

        double vatAmount = totalAmount * VAT_RATE;
        double totalWithVAT = totalAmount + vatAmount;

        // Append VAT and total amounts to the receipt
        receipt.append("\nTotal amount (excl. VAT): $").append(String.format("%.2f", totalAmount));
        receipt.append("\nTotal VAT: $").append(String.format("%.2f", vatAmount));
        receipt.append("\nTotal amount (incl. VAT): $").append(String.format("%.2f", totalWithVAT));

        // Display the final receipt
        JOptionPane.showMessageDialog(null, receipt.toString());

        // Add receipt to the client's records
        if (client != null) {
            StringBuilder purchaseDetails = new StringBuilder();
            purchaseDetails.append("Prescription for ").append(clientName).append(":\n");
            purchaseDetails.append(receipt.toString()); // Assuming 'receipt' is a StringBuilder variable
            client.addReceipt(purchaseDetails.toString());
        } else {
            JOptionPane.showMessageDialog(null, "Error handling client information.");
        }
    }

    public void addDescription(String drugName, String description) {
        drugName = drugName.toLowerCase(); // Convert to lowercase
        for (int i = 0; i < itemList.size(); i++) {
            item item = itemList.get(i);
            if (item.hasSameName(drugName)) {
                item.addDescription(description);
                JOptionPane.showMessageDialog(null,
                        "Description added for " + drugName + ": " + description);
                return;
            }
        }
        JOptionPane.showMessageDialog(null, "Item not found in inventory.");
    }

    public void displayInventory() {
        StringBuilder message = new StringBuilder("\nInventory:\n");
        for (item it : itemList) {
            message.append(it.toString()).append("\n");
        }
        JOptionPane.showMessageDialog(null, message.toString());
    }

    public void removeDrug(String drugName) {
        drugName = drugName.toLowerCase(); // Convert to lowercase
        for (int i = 0; i < itemList.size(); i++) {
            if (itemList.get(i).hasSameName(drugName)) {
                itemList.remove(i);
                JOptionPane.showMessageDialog(null, drugName + " has been removed from inventory.");
                return;
            }
        }
        JOptionPane.showMessageDialog(null, "Drug not found in inventory.");
    }

    // Method to get a client by name
    public Client getClient(String name) {
        for (Client client : clientList) {
            if (client.getName().equalsIgnoreCase(name)) {
                return client;
            }
        }
        return null; // or handle this case as you see fit
    }

    // add a new client
    public void addClient(String clientName) {
        Client existingClient = getClient(clientName);
        if (existingClient != null) {
            JOptionPane.showMessageDialog(null, "Client already exists.");
            return;
        }
        clientList.add(new Client(clientName));
        JOptionPane.showMessageDialog(null, "New client added: " + clientName);
    }

    public boolean removeClient(String clientName) {
        for (int i = 0; i < clientList.size(); i++) {
            if (clientList.get(i).getName().equalsIgnoreCase(clientName)) {
                clientList.remove(i);
                JOptionPane.showMessageDialog(null, "Client removed: " + clientName);
                return true;
            }
        }
        JOptionPane.showMessageDialog(null, "Client not found.");
        return false;
    }

    public double sellItem(String drugName, int soldQuant, double discountRate, String clientName) {
        // Validate inputs
        if (soldQuant <= 0 || discountRate < 0 || discountRate > 100) {
            JOptionPane.showMessageDialog(null, "Invalid quantity or discount rate.");
            return 0.0;
        }

        // Find the item in the inventory
        item sellingItem = null;
        for (item it : itemList) {
            if (it.hasSameName(drugName)) {
                sellingItem = it;
                break;
            }
        }

        if (sellingItem == null) {
            JOptionPane.showMessageDialog(null, "Item not found in inventory.");
            return 0.0;
        }

        if (soldQuant > sellingItem.getQuantity()) {
            JOptionPane.showMessageDialog(null, "Not enough stock available for " + drugName + ".");
            return 0.0;
        }

        double pricePerItem = sellingItem.getPrice();
        double totalPrice = pricePerItem * soldQuant;

        // Apply discount
        double discountAmount = totalPrice * discountRate / 100;
        totalPrice -= discountAmount;

        // Apply VAT
        final double VAT_RATE = 0.20; // 20% VAT
        double vatAmount = totalPrice * VAT_RATE;
        totalPrice += vatAmount;

        // Update the item's quantity in the inventory
        sellingItem.updateQuan(sellingItem.getQuantity() - soldQuant);
        soldItems.add(sellingItem); // Track sold items

        // Prepare the receipt
        String receipt = soldQuant + " " + sellingItem.getItemName() + "(s) sold.\n" +
                "Total price (excl. VAT): $" + String.format("%.2f", totalPrice - vatAmount) + "\n" +
                "VAT: $" + String.format("%.2f", vatAmount) + "\n" +
                "Total price (incl. VAT): $" + String.format("%.2f", totalPrice) + "\n" +
                "Remaining quantity: " + sellingItem.getQuantity();

        // Add receipt to the client
        Client client = getClient(clientName);
        if (client != null) {
            client.addReceipt(receipt);
        } else {
            JOptionPane.showMessageDialog(null, "Client not found.");
        }

        return totalPrice; // Return total price for further processing
    }

    // In StoragePharmacy class
    public void displayClientReceipts(String clientName) {
        Client client = getClient(clientName);
        if (client != null) {
            JOptionPane.showMessageDialog(null, client.getReceipts());
        } else {
            JOptionPane.showMessageDialog(null, "Client not found.");
        }
    }

}
