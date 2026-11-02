# 📊 Tableau Financial Analytics Dashboard Blueprint
**Project:** Personal Expense Tracker - Executive Financial Intelligence Dashboard  
**Target Role:** Data Analyst Intern (PES University BCA 3rd Year)  
**Tool Covered:** Tableau Desktop / Tableau Public  
**Data Source:** `transactions_export.csv` (Exported from your Expense Tracker web app)

---

## 🧭 Overview

Tableau is the leading Business Intelligence & Data Visualization tool used by analytics teams in Bangalore and worldwide.  
In an interview, you can showcase this project by presenting:
1. **Advanced Calculated Fields & Aggregations**
2. **Level of Detail (LOD) Expressions** (The #1 tested Tableau concept!)
3. **Dual-Axis Charts** (Bar + Line Combo, and the famous Tableau Donut Chart)
4. **Interactive Dashboard Actions** (Clicking a category filters the entire dashboard)
5. **Dynamic Parameters** (Top N Category filter)

---

## 🔌 Step 1: Connecting Data & Data Hygiene

1. Open **Tableau Desktop** (or **Tableau Public**).
2. Under **Connect** -> Choose **Text file** -> Select your exported `transactions_export_*.csv` from the Expense Tracker app.
3. Verify Data Types in the Data Source Tab:
   - `Transaction ID`: Number (Whole) -> Right-click and change to **Dimension**.
   - `Date`: **Date** type.
   - `Type`: **String** (Income / Expense).
   - `Category`: **String**.
   - `Amount (INR)`: **Number (decimal)**.
   - `Description`: **String**.

---

## 📐 Step 2: Essential Calculated Fields (Copy & Paste Formulas)

In Tableau, go to **Analysis** -> **Create Calculated Field** and add these formulas:

### 1. Separate Inflow & Outflow Measures
* **Field Name:** `Income Measure`
  ```tableau
  IF [Type] = 'Income' THEN [Amount (INR)] ELSE 0 END
  ```
* **Field Name:** `Expense Measure`
  ```tableau
  IF [Type] = 'Expense' THEN [Amount (INR)] ELSE 0 END
  ```

### 2. Net Savings & Savings Rate
* **Field Name:** `Net Savings`
  ```tableau
  SUM([Income Measure]) - SUM([Expense Measure])
  ```
  *(Default Properties -> Number Format -> Currency Custom: Prefix `₹`, 0 decimals)*

* **Field Name:** `Savings Rate %`
  ```tableau
  [Net Savings] / SUM([Income Measure])
  ```
  *(Default Properties -> Number Format -> Percentage: 1 decimal place)*

* **Field Name:** `Savings Status Color`
  ```tableau
  IF [Savings Rate %] >= 0.20 THEN 'Optimal (>=20%)'
  ELSEIF [Savings Rate %] >= 0 THEN 'Moderate (0-20%)'
  ELSE 'Deficit (<0%)'
  END
  ```

### 3. Needs vs. Wants (50/30/20 Rule Classification)
* **Field Name:** `Budget Classification`
  ```tableau
  IF [Category] IN ('Food', 'Transport', 'Bills', 'Health', 'Education') THEN
      'Needs (Essential)'
  ELSE
      'Wants (Discretionary)'
  END
  ```

### 4. Advanced Level of Detail (LOD) Expressions *(Interview Gold!)*
* **LOD 1: Category % of Total Expense (Unaffected by Category Filters)**
  * **Field Name:** `Category % of Total (LOD)`
    ```tableau
    SUM([Expense Measure]) / MIN({FIXED : SUM([Expense Measure])})
    ```
    *Why interviewers love this:* It proves you understand `{FIXED}` syntax and how to bypass local dimension filters!

* **LOD 2: Average Monthly Burn Rate**
  * **Field Name:** `Avg Monthly Burn Rate (LOD)`
    ```tableau
    {AVG({FIXED DATETRUNC('month', [Date]) : SUM([Expense Measure])})}
    ```

---

## 📈 Step 3: Building the Sheets (Visualizations)

### **Sheet 1: KPI Scorecards (Text BANs - Big Ass Numbers)**
1. Drag `Measure Names` to Columns.
2. Drag `Measure Values` to Text.
3. Keep only: `SUM(Income Measure)`, `SUM(Expense Measure)`, `[Net Savings]`, and `[Savings Rate %]`.
4. Drag `[Savings Status Color]` to Color.

---

### **Sheet 2: Monthly Cash Flow Trend with Savings Rate (Dual-Axis Combo Chart)**
1. Drag `Date` to **Columns** -> Right-click and choose **Month** (Continuous, e.g. *May 2026*).
2. Drag `Income Measure` and `Expense Measure` to **Rows** as a side-by-side bar chart (or drag `Measure Values` to Rows with Bar marks).
3. Drag `[Savings Rate %]` to **Rows** to the right.
4. Right-click the `Savings Rate %` axis on the right -> Click **Dual Axis**.
5. In the **Marks** card:
   - For `Measure Values`: Set mark type to **Bar**.
   - For `Savings Rate %`: Set mark type to **Line** with circles/dots on data points.
6. Right-click the right axis -> Check **Synchronize Axis** (if applicable) or leave formatted as percentage.

---

### **Sheet 3: Category Expense Donut Chart *(Classic Tableau Trick)***
*Tableau doesn't have a native Donut Chart button, so building one proves high competence!*
1. Double-click the **Rows** shelf and type: `MIN(1)`.
2. Double-click **Rows** again and type: `MIN(1)`. (You now have two identical rows).
3. Right-click the second `MIN(1)` pill on Rows -> Select **Dual Axis**.
4. In the **Marks Card**:
   - In **All Marks**: Change mark type to **Pie**.
   - In **First MIN(1) Mark**:
     - Drag `Category` to **Color**.
     - Drag `Expense Measure` to **Angle** and **Size**.
     - Drag `[Category % of Total (LOD)]` to **Label**.
   - In **Second MIN(1) Mark**:
     - Remove all fields from Color/Detail.
     - Set Color to **Pure White** (`#FFFFFF`).
     - Adjust the **Size** slider so it sits cleanly inside the outer pie.
     - Drag `SUM(Expense Measure)` to **Label** (Center of Donut).
5. Right-click the axes and uncheck **Show Header**. Remove zero and grid lines.

---

### **Sheet 4: Top 5 Expense Categories (Pareto Ranking)**
1. Drag `Category` to **Rows**.
2. Drag `Expense Measure` to **Columns**.
3. Sort descending (highest spend at top).
4. Drag `Budget Classification` to **Color** (Sky Blue for Needs, Amber for Wants).
5. Drag `Expense Measure` to **Label** to show values on bars.

---

### **Sheet 5: Day of Week Spending Heatmap**
1. Drag `Date` to **Columns** -> Right-click and select **Weekday** (Discrete: Sunday, Monday, etc.).
2. Drag `Category` to **Rows**.
3. Drag `SUM(Expense Measure)` to **Color** and **Label**.
4. Change mark type to **Square**.
5. Color palette: **Orange-Red-Warm** or **Purple-Blue**.
   - Instantly reveals which categories spike on weekends (e.g. Dining out & Entertainment)!

---

## 🖼️ Step 4: Building the Interactive Dashboard

1. Click **New Dashboard** at the bottom.
2. Set size to **Fixed Size: 1366 x 768** (Standard Laptop/Monitor resolution).
3. Add a **Horizontal Container** for the Top Header:
   - Title: *"💰 Personal Expense Intelligence & Cash Flow Analytics"*.
4. Place **KPI Scorecards** across the top.
5. Place **Monthly Trend (Dual Axis)** on the left (60% width), and **Donut Chart** on the right (40% width).
6. Place **Top Categories Bar** and **Weekday Heatmap** in the bottom row.
7. **Configure Dashboard Actions (Interactive Cross-Filtering)**:
   - Go to **Dashboard** -> **Actions** -> **Add Action** -> **Filter**.
   - Name: `Filter by Category Click`.
   - Source Sheet: `Category Donut Chart`.
   - Target Sheets: `Monthly Cash Flow Trend`, `Top Categories`, and `Transaction Table`.
   - Run Action On: **Select** (Clearing selection will: Show all values).
   - *Now, when an interviewer clicks "Food" on the donut, every visual filters dynamically to Food!*

---

## 💬 Top 5 Tableau Interview Questions & Answers

### **Q1: What are Level of Detail (LOD) expressions, and what is the difference between FIXED, INCLUDE, and EXCLUDE?**
> **Answer:**  
> *"LOD expressions compute values at different levels of granularity than the view:  
> - `{FIXED}` computes an aggregation using only the specified dimensions, ignoring any filters or dimensions currently in the view.  
> - `{INCLUDE}` calculates at a more detailed level than the view (e.g., computing average customer spend within a state view).  
> - `{EXCLUDE}` omits specific dimensions present in the view from the calculation.  
> In my project, I used `{FIXED : SUM([Expense])}` to calculate each category's share of overall expenses without being warped by local category filters."*

---

### **Q2: How did you create the Donut Chart in Tableau?**
> **Answer:**  
> *"Since Tableau does not have a native Donut Chart mark, I used a dual-axis technique with a dummy calculation `MIN(1)` placed twice on the Rows shelf.  
> The first axis was configured as a Pie Chart with `Category` on Color and `Expense Measure` on Angle.  
> The second axis was configured as a smaller white circle placed on top with the total expense in the center label. Once synchronized and axes hidden, it renders a crisp Donut Chart."*

---

### **Q3: What is the difference between a Dimension and a Measure? Between Discrete (Blue) and Continuous (Green)?**
> **Answer:**  
> *"A **Dimension** contains qualitative values (names, categories, dates) used to slice data. A **Measure** contains numerical, quantitative values that can be aggregated (`SUM`, `AVG`).  
> **Discrete (Blue)** pills create row/column headers and separate buckets.  
> **Continuous (Green)** pills create continuous quantitative axes with gradients."*

---

### **Q4: How did you build a Dual-Axis chart in this project?**
> **Answer:**  
> *"I placed `Income` and `Expense` on Rows as bars to display cash volume over time, and added `[Savings Rate %]` to Rows. By right-clicking the savings measure and selecting **Dual Axis**, Tableau overlays both metrics on the same time dimension with two distinct axes and mark types (Bar for cash, Line for percentage)."*

---

### **Q5: What is the Order of Operations in Tableau?**
> **Answer:**  
> *"Tableau processes filters in this strict sequence:  
> 1. Extract Filters  
> 2. Data Source Filters  
> 3. Context Filters  
> 4. Sets / Conditional & Top N / **FIXED LODs**  
> 5. Dimension Filters  
> 6. **INCLUDE / EXCLUDE LODs**  
> 7. Measure Filters  
> 8. Table Calculations  
> Because `FIXED` LODs are evaluated before Dimension Filters, they retain the global total unless a filter is elevated to a **Context Filter**."*
