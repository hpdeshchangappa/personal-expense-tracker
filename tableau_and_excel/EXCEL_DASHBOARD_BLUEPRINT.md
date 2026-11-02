# 📊 Excel Financial Analytics Dashboard Blueprint
**Project:** Personal Expense Tracker - Business Intelligence & Financial Analytics  
**Target Role:** Data Analyst Intern (PES University BCA 3rd Year)  
**Tools Covered:** Microsoft Excel (Formulas, Pivot Tables, Slicers, Conditional Formatting, Dashboards)

---

## 🧭 Overview

In Data Analytics internship interviews, Excel is **tested in almost every technical round**. Recruiters look for your ability to take raw transactional data and transform it into an executive dashboard using:
1. **Dynamic Formulas:** `SUMIFS`, `COUNTIFS`, `AVERAGEIFS`, `XLOOKUP`, `IF(OR(...))`, `TEXT`
2. **Pivot Tables & Pivot Charts:** Aggregating metrics across categories, time dimensions, and types
3. **Interactive Slicers & Timelines:** Allowing non-technical managers to filter by category and date
4. **Conditional Formatting:** Highlighting budget deficits, top expenses, and positive savings

---

## 📁 Step 1: Data Preparation & The "Data" Sheet

1. Export your transactions from your web app:
   - Go to `http://localhost/expense_tracker/analytics.php` -> Click **📥 Export CSV**
   - Open the downloaded CSV in Excel, and save it as **`Expense_Analytics_Dashboard.xlsx`**.
2. Name this first tab **`Data`**.
3. Convert your raw range into an official **Excel Table**:
   - Select any cell in your data (`Ctrl + A`) -> Press **`Ctrl + T`** -> Check **"My table has headers"** -> Click **OK**.
   - In the **Table Design** tab at the top, rename the table to: `TransactionsTable`.
4. Add the following **Calculated Columns** to the right of your table:

| New Column Name | Excel Formula to Paste | Analytical Purpose |
| :--- | :--- | :--- |
| **Year-Month** | `=TEXT([@Date], "yyyy-mm")` | Groups dates for monthly trend analysis. |
| **Month Name** | `=TEXT([@Date], "mmm yyyy")` | Readable time labels (e.g. "May 2026"). |
| **Day of Week** | `=TEXT([@Date], "dddd")` | Analyzes weekend vs. weekday spending velocity. |
| **Is Weekend?** | `=IF(OR([@[Day of Week]]="Saturday", [@[Day of Week]]="Sunday"), "Weekend", "Weekday")` | Behavioral lifestyle segmentation. |
| **Budget Classification** | `=IF(OR([@Category]="Food", [@Category]="Bills", [@Category]="Transport", [@Category]="Health", [@Category]="Education"), "Needs (Essential)", "Wants (Discretionary)")` | Implements the **50/30/20 Budgeting Rule**. |
| **Signed Amount** | `=IF([@Type]="Income", [@Amount], -[@Amount])` | Creates positive inflows and negative outflows for net cash flow calculation. |

---

## 🧮 Step 2: Summary KPI Cards (Formulas)

Create a new tab called **`Dashboard`**. At the top, construct a **KPI Ribbon** using these dynamic Excel formulas:

### 1. Total Income
```excel
=SUMIF(TransactionsTable[Type], "Income", TransactionsTable[Amount])
```
*Format cell as Currency: `₹#,##0.00`*

### 2. Total Expense
```excel
=SUMIF(TransactionsTable[Type], "Expense", TransactionsTable[Amount])
```
*Format cell as Currency: `₹#,##0.00`*

### 3. Net Savings (Cash Flow)
```excel
=Total_Income_Cell - Total_Expense_Cell
```
*Format cell as Currency: `₹#,##0.00` (Use Conditional Formatting: Green if > 0, Red if < 0)*

### 4. Net Savings Rate (%)
```excel
=IF(Total_Income_Cell > 0, (Total_Income_Cell - Total_Expense_Cell) / Total_Income_Cell, 0)
```
*Format cell as Percentage: `0.0%` (Target benchmark: $\ge 20\%$)*

### 5. Daily Burn Rate
```excel
=Total_Expense_Cell / COUNT(UNIQUE(FILTER(TransactionsTable[Date], TransactionsTable[Type]="Expense")))
```
*(Or in older Excel without `FILTER`: `=Total_Expense_Cell / COUNTIF(TransactionsTable[Type], "Expense")` for Average per Txn).*

### 6. Top Spending Category (`XLOOKUP` & `INDEX/MATCH`)
```excel
=INDEX(PivotSheet!A5:A15, MATCH(MAX(PivotSheet!B5:B15), PivotSheet!B5:B15, 0))
```

---

## 📊 Step 3: Building the Pivot Tables (Tab: `Pivot_Calculations`)

Create a dedicated tab named **`Pivot_Calculations`** to store the pivot engines powering your dashboard:

### **Pivot Table 1: Monthly Cash Flow Trend**
1. Click `TransactionsTable` -> **Insert** -> **PivotTable** -> Place in `Pivot_Calculations`.
2. **Rows:** `Month Name` (or `Year-Month` sorted chronologically).
3. **Columns:** `Type` (Income, Expense).
4. **Values:** `Sum of Amount`.
5. **Chart to insert:** **Clustered Column Chart** or **Combo Chart** (Income in Green bars, Expense in Red bars).

### **Pivot Table 2: Category Breakdown (Composition)**
1. **Insert PivotTable**.
2. **Rows:** `Category`.
3. **Filters:** `Type` = Select only **"Expense"**.
4. **Values:** Drag `Amount` twice:
   - First instance: `Sum of Amount` (Format as Currency `₹#,##0`).
   - Second instance: Right-click -> **Show Values As** -> **% of Column Total**.
5. **Sort:** Right-click any amount -> **Sort Largest to Smallest**.
6. **Chart to insert:** **Doughnut Chart** (Donut Chart) with category data labels.

### **Pivot Table 3: Needs vs. Wants (50/30/20 Rule)**
1. **Insert PivotTable**.
2. **Rows:** `Budget Classification` (Needs vs Wants).
3. **Filters:** `Type` = **Expense**.
4. **Values:** `Sum of Amount` & `% of Column Total`.
5. **Chart to insert:** **Pie Chart** or **Stacked Bar Chart**.

### **Pivot Table 4: Day-of-Week Behavioral Spending**
1. **Insert PivotTable**.
2. **Rows:** `Day of Week` (Sort manually: Monday through Sunday).
3. **Filters:** `Type` = **Expense**.
4. **Values:** `Sum of Amount` & `Count of Amount` (Transaction Frequency).
5. **Chart to insert:** **Horizontal Bar Chart** or **Line Chart**.

---

## 🎛️ Step 4: Assembling the Interactive Dashboard

On your **`Dashboard`** sheet:
1. **Grid Setup:**
   - Turn off Gridlines: Go to **View** -> Uncheck **Gridlines** (gives a polished SaaS application look!).
   - Set background fill of row 1 to 4 to a sleek header (e.g. Deep Navy `#1E293B` or Gradient Purple `#667EEA`).
2. **Cut and Paste Pivot Charts** from `Pivot_Calculations` into the `Dashboard` sheet.
3. **Add Interactive Slicers**:
   - Select any Pivot Chart -> Go to **PivotChart Analyze** tab -> **Insert Slicer**.
   - Check: **`Category`**, **`Type`**, and **`Budget Classification`**.
   - Go to **Insert** -> **Timeline** -> Select **`Date`**.
4. **Link Slicers to ALL Pivot Tables (CRUCIAL!)**:
   - Right-click each Slicer -> Select **Report Connections...** -> Check **EVERY** Pivot Table on `Pivot_Calculations`.
   - Now, when you click any category or timeline filter, **all charts and numbers update simultaneously!**

---

## 💬 Top 5 Excel Interview Questions for Data Analysts

### **Q1: What is the difference between VLOOKUP and XLOOKUP?**
> **Answer:**  
> *"VLOOKUP only searches from left to right, requires hardcoded column index numbers (which break when columns are inserted), and defaults to approximate match.  
> `XLOOKUP` searches in any direction (left or right), uses exact match by default, handles missing values gracefully with its `if_not_found` argument, and is much less prone to broken references."*

### **Q2: How do you handle outliers in an Excel dataset?**
> **Answer:**  
> *"I calculate the first quartile using `=QUARTILE.INC(range, 1)` and third quartile with `=QUARTILE.INC(range, 3)`.  
> Then calculate the Interquartile Range: $\text{IQR} = Q_3 - Q_1$.  
> Any value greater than $Q_3 + (1.5 \times \text{IQR})$ or less than $Q_1 - (1.5 \times \text{IQR})$ is flagged using a logical formula `=IF(amount > Upper_Threshold, "Outlier", "Normal")` or highlighted with Conditional Formatting."*

### **Q3: What are Slicers and how do you connect one Slicer to multiple Pivot Tables?**
> **Answer:**  
> *"Slicers are interactive visual filters. To connect a single Slicer across multiple Pivot Tables, right-click the Slicer, choose **Report Connections**, and select the target Pivot Tables. This enables synchronized cross-filtering across the entire dashboard."*

### **Q4: How did you calculate the 50/30/20 budgeting rule in Excel?**
> **Answer:**  
> *"I created a calculated column in my data table using `IF(OR(Category="Food", Category="Bills", ...), "Needs", "Wants")`.  
> Then in my Pivot Table, I displayed the `Amount` field both as an absolute sum and as a `% of Column Total`, allowing comparison against the 50% Needs, 30% Wants, and 20% Savings target benchmarks."*

### **Q5: What is the difference between COUNT, COUNTA, and COUNTBLANK?**
> **Answer:**  
> *" `COUNT` counts only cells containing numerical values.  
> `COUNTA` counts non-empty cells (numbers, text, logical values, dates).  
> `COUNTBLANK` counts empty or blank cells."*
