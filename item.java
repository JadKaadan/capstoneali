import java.util.ArrayList;

class item {
    String itemName;
    String description;
    int quantity;
    double price;

    // Static list to hold all items
    static ArrayList<item> predefinedItems = new ArrayList<>();

    // Static block to initialize predefined items
    static {
        predefinedItems.add(new item("panadol", "headache migraine muscleache ", 100, 2.99));
        predefinedItems.add(new item("Aspirin", "fever muscleaches cold ", 100, 3.49));
        predefinedItems.add(new item("Strepsils", "mouth infections, sore throat", 50, 3.49));
        // objects
    }// samples

    // Method to get predefined items
    public static ArrayList<item> getPredefinedItems() {
        return predefinedItems;
    }// method to call the description to call the default objects

    // Existing constructor
    public item(String itemName, String description, int quantity, double price) {
        this.itemName = itemName.toLowerCase(); // Convert to lowercase
        this.description = description;
        this.quantity = quantity;
        this.price = price;
    }

    // Additional constructor for selling items
    public item(String itemName, int soldQuant, double sellPrice) {
        this.itemName = itemName.toLowerCase(); // Convert to lowercase
        this.quantity = soldQuant;
        this.price = sellPrice;
    }

    public boolean hasSameName(String otherName) {
        return this.itemName.equalsIgnoreCase(otherName);
    }

    public String toString() {
        return "Item: " + itemName + "\nDescription: " + description + "\nRemaining Quantity: " + quantity
                + "\nPrice: $" + price + "\n";
    }

    // Method to add a special description for an item
    public void addDescription(String newDescription) {
        this.description = newDescription;
        System.out.println("Description added for " + itemName + ": " + description);
    }

    // Method to update the description of an item
    public void updatedesc(int newdesc) {
        this.quantity = newdesc;
        System.out.println("Quantity updated for " + itemName + ": " + description);
    }

    // Method to update the quantity of an item
    public void updateQuan(int newQuan) {
        this.quantity = newQuan;
        System.out.println("Quantity updated for " + itemName + ": " + quantity);
    }

    // Method to update the price of an item
    public void updateP(double newP) {
        this.price = newP;
        System.out.println("Price updated for " + itemName + ": $" + newP);
    }

    // Method to sell a specific quantity of an item
    public void sellitem(int soldQuant) {
        if (soldQuant <= quantity) {
            quantity -= soldQuant;
            System.out.println(
                    soldQuant + " " + itemName + "(s) sold to a new customer. Remaining quantity: " + quantity);

        } else {
            System.out.println("Not enough " + itemName + " in stock.");
        }
    }

    public String getItemName() {
        return itemName;
    }

    public int getQuantity() {
        return quantity;
    }

    public double getPrice() {
        return price;
    }

    public String getDescription() {
        return description;
    }
}
