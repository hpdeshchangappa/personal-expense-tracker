# 📊 Financial Data Analytics Portfolio & Interview Guide
**Candidate Profile:** 3rd Year BCA Student, PES University (Bengaluru)  
**Target Role:** Data Analytics Intern / Junior Data Analyst  
**Project:** Personal Expense Tracker & Financial Intelligence System

---

## 🚀 1. Executive Project Overview

This project extends a transactional Expense Tracker into an **interactive business intelligence and financial analytics platform**. It demonstrates proficiency in the 3 pillars of Data Analytics:
1. **Analytical SQL & Database Querying** (Aggregations, conditional metrics, date dimensions).
2. **Business KPIs & Financial Metrics** (Savings Rate, Burn Rate, 50/30/20 rule, Pareto ranking).
3. **Exploratory Data Analysis (EDA) & Visualization** (Chart.js dashboards, Python Pandas statistical analysis, and IQR outlier detection).

---

## 📈 2. What Was Built in This Project

| Component | Technology | Analytical Function |
| :--- | :--- | :--- |
| **Interactive Dashboard** | [`analytics.php`](file:///c:/xampp/htdocs/expense_tracker/analytics.php) | Real-time web analytics, dynamic period filters (This Month, Last 30 Days, Year, All Time). |
| **KPI Metrics Engine** | MySQL & PHP | Computes Net Savings Rate (%), Daily Burn Rate (₹/day), Average Ticket Size, and Transaction Velocity. |
| **Time-Series Analysis** | Chart.js Bar & Line | Monthly Cash Flow Trend (Income vs Expense variance across 12 rolling months). |
| **Composition Analysis** | Chart.js Doughnut | Categorical spending share and distribution breakdown. |
| **Behavioral Analytics** | Chart.js Bar Chart | Day-of-Week spending volume (Monday to Sunday) to identify weekend lifestyle spikes. |
| **Data Extraction (ETL)** | [`export_csv.php`](file:///c:/xampp/htdocs/expense_tracker/export_csv.php) | Streams clean, normalized `.csv` datasets for Excel, Power BI, or Python. |
| **Python EDA Script** | [`analytics_python/expense_eda.py`](file:///c:/xampp/htdocs/expense_tracker/analytics_python/expense_eda.py) | Standalone Pandas/Seaborn script for descriptive statistics, IQR anomaly detection, and Pareto 80/20 curves. |

---

## 📝 3. Resume Bullet Points (Tailored for BCA 3rd Year)

Copy and adapt these bullet points for your resume under the **Projects** section:

> ### **Personal Expense Tracker with Automated Data Analytics**  
> *Tech Stack: SQL (MySQL), Python (Pandas, Seaborn), PHP, Chart.js, HTML5/CSS3*
> - **Engineered an end-to-end financial intelligence platform** tracking user cash flow; developed responsive dashboards using Chart.js to visualize multi-month income vs. expense trends and category distributions.
> - **Formulated analytical SQL queries** with conditional aggregation (`SUM(CASE WHEN...)`, `DATE_FORMAT`, `GROUP BY`) to measure essential financial KPIs including Net Savings Rate, Daily Burn Rate, and 50/30/20 Needs vs. Wants allocation.
> - **Built an automated ETL data pipeline** generating structured CSV extracts to enable downstream statistical modeling and exploratory data analysis (EDA).
> - **Implemented an anomaly detection algorithm in Python (Pandas/NumPy)** utilizing the Interquartile Range (IQR) method ($Q_3 + 1.5 \times \text{IQR}$) to detect spending spikes and outlier purchases.
> - **Conducted Pareto (80/20) cost-driver analysis**, identifying the primary 20% of expenditure categories responsible for ~80% of total outflow.

---

## 🎯 4. Top 5 Data Analyst Interview Questions & Sample Answers

### **Q1: "Can you explain how you calculated the Savings Rate and Burn Rate in SQL?"**
> **Answer:**  
> *"In SQL, I used conditional aggregation with `SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END)` to calculate total inflow and `SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END)` for total outflow within a single query pass.  
> The **Net Savings Rate** is calculated as:  
> $$\text{Savings Rate} = \frac{\text{Total Income} - \text{Total Expense}}{\text{Total Income}} \times 100$$  
> For **Daily Burn Rate**, I queried `COUNT(DISTINCT transaction_date)` across expense records to find active spending days, and divided total expense by active days. This avoids distorting the daily spend on days where no transactions occurred."*

---

### **Q2: "Why did you measure both Mean and Median for expense transactions?"**
> **Answer:**  
> *"Personal expense datasets are almost always **right-skewed** (positive skewness). A user might have many small transactions of ₹100–₹500 for food or travel, but occasionally make a large ₹20,000 purchase for electronics or college fees.  
> In a right-skewed distribution, the **Mean** gets dragged upwards by extreme values, giving a misleading impression of a person's typical spend. The **Median** represents the 50th percentile and is robust against outliers, providing a much more realistic picture of central tendency."*

---

### **Q3: "How does your Anomaly / Outlier Detection work?"**
> **Answer:**  
> *"I implemented the **Tukey's Fences / Interquartile Range (IQR)** method in Python using Pandas:
> 1. Calculate the first quartile $Q_1$ (25th percentile) and third quartile $Q_3$ (75th percentile) of expenses.
> 2. Determine $\text{IQR} = Q_3 - Q_1$.
> 3. Define the upper threshold as:
>    $$\text{Upper Bound} = Q_3 + (1.5 \times \text{IQR})$$
> Any transaction exceeding this bound is flagged as an outlier spike. In financial analysis, this automatically isolates unusual events (such as medical emergencies or impulse luxury splurges) from routine recurring expenses."*

---

### **Q4: "What is the 50/30/20 budgeting rule and how did you model it in data?"**
> **Answer:**  
> *"The 50/30/20 rule is a recognized personal finance framework:
> - **50% Needs (Essential):** Food, Bills, Rent, Transport, Health, Education.
> - **30% Wants (Discretionary):** Shopping, Entertainment, Dining out.
> - **20% Savings:** Inflow minus outflow.
> 
> In my analytics logic, I classified transactions using `CASE WHEN category IN ('Food', 'Transport', 'Bills', 'Health', 'Education') THEN 'Needs' ELSE 'Wants' END`. I then calculated the percentage share of each bucket against total outflow and compared the user's savings rate against the 20% benchmark."*

---

### **Q5: "What are the advantages of using Chart.js with PHP/MySQL vs exporting to Python/PowerBI?"**
> **Answer:**  
> *"In production systems, operational dashboards need to be available in real time to the end user directly in their web browser. Chart.js provides interactive, zero-latency canvas charts directly from the application's relational database.  
> However, for deep analytical workflows (statistical modeling, hypothesis testing, machine learning, and correlation analysis), extracting the data via CSV into Python (Pandas/Matplotlib) or Power BI is superior because it allows multi-variate statistical testing without burdening the production transactional database."*

---

## 🛠️ 5. How to Run the Python Analysis

1. Start your XAMPP server (Apache & MySQL).
2. Go to your browser: `http://localhost/expense_tracker/analytics.php`
3. Click **📥 Export CSV** (or download from the table).
4. Open a terminal in `c:\xampp\htdocs\expense_tracker\analytics_python`:
   ```bash
   pip install pandas numpy matplotlib seaborn
   python expense_eda.py
   ```
5. Check the generated charts in `charts_output/` (`01_category_breakdown.png`, `02_outlier_detection_boxplot.png`, `03_monthly_cashflow_trend.png`). You can add these screenshots directly to your GitHub repo `README.md`!

---

## 📊 6. Excel & Tableau Analytics Integration

For Data Analyst internships in Bengaluru (PES University placements & off-campus), **Excel and Tableau are the two most critical tools tested**.

### 📗 Microsoft Excel Implementation
- **Guide & Tutorial:** [`tableau_and_excel/EXCEL_DASHBOARD_BLUEPRINT.md`](file:///c:/xampp/htdocs/expense_tracker/tableau_and_excel/EXCEL_DASHBOARD_BLUEPRINT.md)
- **Workbook Generator:** Run `python generate_excel_workbook.py` inside `tableau_and_excel/` to generate `Expense_Analytics_Dashboard.xlsx`.
- **Key Excel Competencies Demonstrated:**
  * **Dynamic Formulas:** `SUMIF`, `SUMIFS`, `COUNTIFS`, `AVERAGEIFS`, `IF(OR(...))`, `TEXT(date, "mmm yyyy")`
  * **Pivot Tables & Calculations:** Grouped by Month, Category, and Needs vs. Wants (`% of Column Total`).
  * **Interactive Slicers & Timelines:** Connected to all Pivot Tables via **Report Connections**.
  * **Conditional Formatting:** Highlighting budget deficits and outlier expenses.

---

### 🎨 Tableau Desktop / Public Implementation
- **Guide & Tutorial:** [`tableau_and_excel/TABLEAU_DASHBOARD_BLUEPRINT.md`](file:///c:/xampp/htdocs/expense_tracker/tableau_and_excel/TABLEAU_DASHBOARD_BLUEPRINT.md)
- **Data Source:** Connect directly to `transactions_export.csv`.
- **Key Tableau Competencies Demonstrated:**
  * **Level of Detail (LOD) Expressions:** `{FIXED : SUM([Expense Measure])}` for global category percentage share unaffected by dimension filters.
  * **Dual-Axis Charts:**
    1. **Monthly Cash Flow Trend:** Bar marks for Income & Expense with secondary Line axis for `Savings Rate %`.
    2. **Tableau Donut Chart:** Built using dual `MIN(1)` pills on Rows.
  * **Interactive Dashboard Actions:** Filter action configured so clicking any slice on the Category Donut filters the entire dashboard.
  * **Needs vs Wants 50/30/20 Classification:** Formulated via calculated field.

---

### 💼 Combined Resume Bullet Points (Excel + Tableau + SQL + Python)

> - **Architected a multi-platform Financial Intelligence solution** extracting transactional records from MySQL into **Tableau** and **Excel** to monitor cash flow, burn rate, and discretionary spending trends.
> - **Built an executive Tableau Dashboard** utilizing **Level of Detail (LOD) expressions (`{FIXED}`)**, dual-axis combo charts (Monthly Volume vs. Savings Rate Line), and custom Donut Charts with interactive filter actions.
> - **Designed an automated Excel Financial Model** with **Pivot Tables, Slicers, Timeline controls, and multi-condition formulas (`SUMIFS`, `XLOOKUP`, `COUNTIFS`)**, implementing the 50/30/20 budgeting framework.
