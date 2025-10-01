
import java.awt.*;
import java.awt.event.*;
import javax.swing.*;

public class PharmacyDriverMain implements ActionListener {
    private static StoragePharmacy inventory = new StoragePharmacy();
    private JFrame frame;
    private JButton[] buttons = new JButton[11]; // Increased the size to accommodate 11 buttons
    private static final String[] buttonLabels = {
            "Enter new Drug Name:", "Add a special description:", "Update Price:",
            "Update Quantity:", "Sell Item:", "Refer to a Doctor:",
            "View Inventory:", "Remove Drug:", "Manage Clients:", "Exit Pharmacy"
    };

    public PharmacyDriverMain() {
        initializeFrame();
        addBackground();
        addButtons();
        frame.setVisible(true);
    }

    private void initializeFrame() {
        frame = new JFrame();
        ImageIcon image = new ImageIcon("logoo.jpg");
        frame.setIconImage(image.getImage());
        frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        frame.setBounds(0, 0, 1000, 1000); // Changed bounds to (0, 0) for proper display
        frame.setLayout(null);
        frame.setResizable(false);
    }

    private void addBackground() {
        ImageIcon backgroundImage = new ImageIcon("pharmacy.jpg");
        Image img = backgroundImage.getImage().getScaledInstance(1000, 1000, Image.SCALE_SMOOTH);
        backgroundImage = new ImageIcon(img);
        JLabel backgroundLabel = new JLabel(backgroundImage);
        backgroundLabel.setBounds(0, 0, 1000, 1000);
        frame.setContentPane(backgroundLabel);
    }

    private void addButtons() {
        Container container = frame.getContentPane();
        container.setLayout(null);
        buttons = new JButton[buttonLabels.length];
        for (int i = 0; i < buttonLabels.length; i++) {
            buttons[i] = new JButton(buttonLabels[i]);
            buttons[i].setBounds(420, 50 + (i * 50), 200, 40);
            buttons[i].addActionListener(this);
            container.add(buttons[i]);
        }
    }

    public static void main(String[] args) {
        new PharmacyDriverMain();
    }

    @Override
    public void actionPerformed(ActionEvent e) {
        for (int i = 0; i < buttons.length; i++) {
            if (e.getSource() == buttons[i]) {
                handleUserInput(i + 1);
                break;
            }
        }
    }

    private void handleUserInput(int choice) {
        switch (choice) {
            case 1:
                NewDrugEntry();
                break;
            case 2:
                AddDescription();
                break;
            case 3:
                UpdatePrice();
                break;
            case 4:
                UpdateQuantity();
                break;
            case 5:
                SellItem();
                break;
            case 6:
                ReferToDoctor();
                break;
            case 7:
                inventory.displayInventory();
                break;
            case 8:
                RemoveDrug();
                break;
            case 9:
                ManageClient();
                break;

            case 10:
                JOptionPane.showMessageDialog(null, "Exiting Pharmacy Management.");
                System.exit(0);
                break;

            default:
                JOptionPane.showMessageDialog(null, "Invalid choice. Please enter a number between 1 and 11.");
                break;
        }
    }

    private void NewDrugEntry() {
        // Logic for adding a new drug
        String drugName = JOptionPane.showInputDialog(null, "Enter Drug Name:");
        String inputDescription = JOptionPane.showInputDialog(null, "Enter the description of the Drug:");
        int initialQuantity = Integer.parseInt(JOptionPane.showInputDialog(null, "Enter Initial Quantity:"));
        double initialPrice = Double.parseDouble(JOptionPane.showInputDialog(null, "Enter Initial Price: $"));
        inventory.addItem(new item(drugName, inputDescription, initialQuantity, initialPrice));
    }

    private void AddDescription() {
        String descDrugName = JOptionPane.showInputDialog(null, "Enter Drug Name to add a description:");
        String drugDescription = JOptionPane.showInputDialog(null, "Enter Description:");
        inventory.addDescription(descDrugName, drugDescription);
    }

    private void UpdatePrice() {
        String updatePriceName = JOptionPane.showInputDialog(null, "Enter Drug Name to update price:");
        double newPrice = Double.parseDouble(JOptionPane.showInputDialog(null, "Enter New Price: $"));
        inventory.updatePrice(updatePriceName, newPrice);
    }

    private void UpdateQuantity() {
        String updateQuantityName = JOptionPane.showInputDialog(null, "Enter Drug Name to update quantity:");
        int newQuantity = Integer.parseInt(JOptionPane.showInputDialog(null, "Enter New Quantity:"));
        inventory.updateQuantity(updateQuantityName, newQuantity);
    }

    private void SellItem() {
        String[] drugNames = inventory.getItemNames();
        JComboBox<String> drugNameComboBox = new JComboBox<>(drugNames);
        JOptionPane.showMessageDialog(null, drugNameComboBox, "Select Drug Name to sell:",
                JOptionPane.QUESTION_MESSAGE);

        String selectedDrugName = (String) drugNameComboBox.getSelectedItem();

        // Check if a drug name is selected
        if (selectedDrugName == null || selectedDrugName.trim().isEmpty()) {
            JOptionPane.showMessageDialog(null, "Please select a drug to sell.");
            return;
        }
        int soldQuant = Integer.parseInt(JOptionPane.showInputDialog(null, "Enter Quantity Sold:"));

        String clientName = JOptionPane.showInputDialog(null, "Enter Client Name:");

        Client client = inventory.getClient(clientName);
        if (client == null) {
            JOptionPane.showMessageDialog(null, "Client not found.");
            return; // Exit if the client is not found
        }

        double discountRate = 0.0;
        double discountAmount = 0.0; // Variable to store the discount amount

        // Ask the user if they want to apply a discount
        int discountChoice = JOptionPane.showConfirmDialog(null,
                "Do you want to apply a discount?", "Discount Confirmation", JOptionPane.YES_NO_OPTION);

        if (discountChoice == JOptionPane.YES_OPTION) {
            String codeoutput = JOptionPane.showInputDialog("Enter code: ");
            if (codeoutput.equalsIgnoreCase("yey code")) {
                // If yes, ask for the discount percentage
                String discountPercentageStr = JOptionPane.showInputDialog(null, "Enter the discount percentage:");
                discountRate = Double.parseDouble(discountPercentageStr);
            }
        }

        double totalPriceBeforeVAT = inventory.sellItem(selectedDrugName, soldQuant, discountRate, clientName);
        discountAmount = totalPriceBeforeVAT * discountRate / 100;
        double totalPrice = totalPriceBeforeVAT - discountAmount;

        // Add VAT to the total price
        double vatAmount = totalPrice * inventory.VAT_RATE;
        totalPrice += vatAmount;

        // Display the receipt including discount information
        if (totalPriceBeforeVAT > 0) {
            String receipt = soldQuant + " " + selectedDrugName + "(s) sold.\n" +
                    "Total price (excl. VAT): $" + String.format("%.2f", totalPriceBeforeVAT) + "\n" +
                    "Discount applied: " + discountRate + "%\n" +
                    "Discount amount: $" + String.format("%.2f", discountAmount) + "\n" + // Include discount amount
                    "VAT: $" + String.format("%.2f", vatAmount) + "\n" +
                    "Total price (incl. VAT): $" + String.format("%.2f", totalPrice) + "\n";

            // Add receipt to the client if found
            if (client != null) {
                client.addReceipt(receipt);
            }

            JOptionPane.showMessageDialog(null, receipt);
        } else {
            JOptionPane.showMessageDialog(null, "Sale not completed.");
        }
    }

    private void ReferToDoctor() {
        // Help from Doctor
        String customerSentence = JOptionPane.showInputDialog(null, "Enter your symptoms:");
        inventory.referToDoctor(customerSentence);
    }

    private void RemoveDrug() {
        String removeDrugName = JOptionPane.showInputDialog(null, "Enter Drug Name to remove:");
        inventory.removeDrug(removeDrugName);
    }

    private void ManageClient() {
        String[] options = { "Add Client", "Remove Client", "View Client Receipts" };
        int choice = JOptionPane.showOptionDialog(
                null,
                "Select an option:",
                "Manage Clients",
                JOptionPane.DEFAULT_OPTION,
                JOptionPane.QUESTION_MESSAGE,
                null,
                options,
                options[0]);

        switch (choice) {
            case 0: // Add Client
                String clientNameToAdd = JOptionPane.showInputDialog(null, "Enter Client Name:");
                if (clientNameToAdd != null && !clientNameToAdd.trim().isEmpty()) {
                    inventory.addClient(clientNameToAdd);
                } else {
                    JOptionPane.showMessageDialog(null, "Invalid client name.");
                }
                break;
            case 1: // Remove Client
                String clientNameToRemove = JOptionPane.showInputDialog(null, "Enter Client Name to remove:");
                if (clientNameToRemove != null && !clientNameToRemove.trim().isEmpty()) {
                    boolean removed = inventory.removeClient(clientNameToRemove);
                    if (!removed) {
                        JOptionPane.showMessageDialog(null, "Client not found or could not be removed.");
                    }
                } else {
                    JOptionPane.showMessageDialog(null, "Invalid client name.");
                }
                break;
            case 2: // View Client Receipts
                String clientNameToView = JOptionPane.showInputDialog(null, "Enter Client Name to view receipts:");
                if (clientNameToView != null && !clientNameToView.trim().isEmpty()) {
                    Client client = inventory.getClient(clientNameToView);
                    if (client != null) {
                        String receipts = client.getReceipts();
                        JOptionPane.showMessageDialog(null,
                                receipts.isEmpty() ? "No receipts for this client." : receipts);
                    } else {
                        JOptionPane.showMessageDialog(null, "Client not found.");
                    }
                } else {
                    JOptionPane.showMessageDialog(null, "Invalid client name.");
                }
                break;
            default:
                JOptionPane.showMessageDialog(null, "Invalid option.");
                break;
        }
    }
}
