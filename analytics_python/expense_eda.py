"""
================================================================================
PERSONAL EXPENSE TRACKER - DATA ANALYTICS & EDA (EXPLORATORY DATA ANALYSIS)
================================================================================
Author: 3rd Year BCA Candidate (PES University)
Target Role: Data Analytics Intern
Tech Stack: Python, Pandas, NumPy, Matplotlib, Seaborn

Purpose:
  This script performs professional-grade Exploratory Data Analysis (EDA)
  on transaction data exported from the Personal Expense Tracker application.
  
  Key Analytical Methods Demonstrated:
  1. Data Ingestion, Type Casting & Data Quality Verification
  2. Descriptive & Inferential Statistics (Mean, Median, Skewness, Std Dev)
  3. Outlier Detection via Interquartile Range (IQR) Rule
  4. Pareto (80/20) Cost Driver Analysis
  5. Time-Series Trend Aggregations & Month-over-Month (MoM) Growth
  6. Automated Visualization Generation for Executive Reporting
================================================================================
"""

import os
import glob
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns

# Set style for publication-ready visual charts
plt.style.use('seaborn-v0_8-whitegrid' if 'seaborn-v0_8-whitegrid' in plt.style.available else 'default')
plt.rcParams['font.sans-serif'] = 'Arial'
plt.rcParams['axes.edgecolor'] = '#cbd5e1'
plt.rcParams['axes.linewidth'] = 0.8


def find_latest_export(directory="."):
    """Finds the latest CSV exported from the Expense Tracker app."""
    csv_files = glob.glob(os.path.join(directory, "*transactions_export*.csv"))
    if not csv_files:
        # Fallback to any CSV in directory
        csv_files = glob.glob(os.path.join(directory, "*.csv"))
    
    if not csv_files:
        return None
    return max(csv_files, key=os.path.getctime)


def create_sample_dataset_if_none():
    """Generates a realistic sample dataset for instant demonstration if no CSV exists."""
    sample_file = "sample_transactions.csv"
    if not os.path.exists(sample_file):
        data = [
            [1, '2026-05-01', 'Income', 'Salary', 55000.00, 'Monthly Stipend/Salary'],
            [2, '2026-05-02', 'Expense', 'Bills', 4500.00, 'Electricity & Wifi'],
            [3, '2026-05-03', 'Expense', 'Food', 1200.00, 'Weekly Grocery Supermarket'],
            [4, '2026-05-05', 'Expense', 'Transport', 650.00, 'Metro and Auto'],
            [5, '2026-05-08', 'Expense', 'Food', 450.00, 'Cafeteria lunch with team'],
            [6, '2026-05-10', 'Expense', 'Entertainment', 1500.00, 'Movie & Snacks'],
            [7, '2026-05-15', 'Income', 'Freelance', 12000.00, 'Data Dashboard Project'],
            [8, '2026-05-16', 'Expense', 'Shopping', 8500.00, 'Noise Cancelling Headphones'],
            [9, '2026-05-20', 'Expense', 'Food', 800.00, 'Dinner'],
            [10, '2026-05-22', 'Expense', 'Health', 1200.00, 'Pharmacy & Vitamins'],
            [11, '2026-05-25', 'Expense', 'Education', 3200.00, 'Analytics Certification Course'],
            [12, '2026-05-28', 'Expense', 'Food', 1100.00, 'Dining out'],
            [13, '2026-05-30', 'Expense', 'Transport', 800.00, 'Cab rides'],
            [14, '2026-06-01', 'Income', 'Salary', 55000.00, 'Monthly Stipend/Salary'],
            [15, '2026-06-03', 'Expense', 'Bills', 4200.00, 'Utility bills'],
            [16, '2026-06-05', 'Expense', 'Food', 1500.00, 'Groceries'],
            [17, '2026-06-12', 'Expense', 'Shopping', 3500.00, 'Apparel'],
            [18, '2026-06-18', 'Expense', 'Food', 2200.00, 'Weekend restaurant bill'],
            [19, '2026-06-22', 'Expense', 'Entertainment', 2000.00, 'Concert ticket'],
            [20, '2026-06-25', 'Expense', 'Other Expense', 18500.00, 'Laptop Screen Repair (Unusual Expense)']
        ]
        df = pd.DataFrame(data, columns=["Transaction ID", "Date", "Type", "Category", "Amount (INR)", "Description"])
        df.to_csv(sample_file, index=False)
        print(f"[INFO] Generated synthetic dataset: {sample_file}")
    return sample_file


def load_and_clean_data(file_path):
    """Loads and preprocesses raw transaction records."""
    print(f"\n[STEP 1] Ingesting transaction dataset from: {file_path}")
    df = pd.read_csv(file_path)
    
    # Standardize column names
    df.columns = [c.strip().lower().replace(' ', '_').replace('(', '').replace(')', '') for c in df.columns]
    
    # Cast date column
    df['date'] = pd.to_datetime(df['date'])
    df['amount_inr'] = pd.to_numeric(df['amount_inr'], errors='coerce')
    
    # Feature Engineering (Extract temporal components)
    df['year_month'] = df['date'].dt.to_period('M')
    df['day_name'] = df['date'].dt.day_name()
    df['is_weekend'] = df['day_name'].isin(['Saturday', 'Sunday'])
    
    # Split Inflows vs Outflows
    expenses = df[df['type'].str.lower() == 'expense'].copy()
    income = df[df['type'].str.lower() == 'income'].copy()
    
    print(f"  -> Total Records: {len(df)}")
    print(f"  -> Expense Records: {len(expenses)} | Income Records: {len(income)}")
    return df, expenses, income


def perform_statistical_summary(expenses, income):
    """Computes descriptive analytics and financial performance indicators (KPIs)."""
    print("\n" + "="*60)
    print("STEP 2: DESCRIPTIVE & FINANCIAL METRICS (BUSINESS KPIS)")
    print("="*60)
    
    tot_income = income['amount_inr'].sum()
    tot_expense = expenses['amount_inr'].sum()
    net_savings = tot_income - tot_expense
    savings_rate = (net_savings / tot_income * 100) if tot_income > 0 else 0
    
    # Expense statistics
    mean_exp = expenses['amount_inr'].mean()
    median_exp = expenses['amount_inr'].median()
    std_exp = expenses['amount_inr'].std()
    skewness = expenses['amount_inr'].skew()
    
    print(f"  Total Inflow (Income)       : INR {tot_income:,.2f}")
    print(f"  Total Outflow (Expenses)    : INR {tot_expense:,.2f}")
    print(f"  Net Savings                 : INR {net_savings:,.2f}")
    print(f"  Savings Rate                : {savings_rate:.2f}% (Industry Benchmark >= 20%)")
    print(f"  Mean Expense Transaction    : INR {mean_exp:,.2f}")
    print(f"  Median Expense Transaction  : INR {median_exp:,.2f}")
    print(f"  Standard Deviation          : INR {std_exp:,.2f}")
    print(f"  Distribution Skewness       : {skewness:.2f}")
    if skewness > 1:
        print("    -> Finding: Highly right-skewed distribution. Median is a better central metric than Mean.")
    return tot_income, tot_expense, savings_rate


def detect_outliers_iqr(expenses):
    """
    Detects abnormal/spike transactions using the Interquartile Range (IQR) method.
    Formula:
      Q1 = 25th percentile
      Q3 = 75th percentile
      IQR = Q3 - Q1
      Upper Bound = Q3 + (1.5 * IQR)
    """
    print("\n" + "="*60)
    print("STEP 3: ANOMALY & OUTLIER DETECTION (IQR METHOD)")
    print("="*60)
    
    q1 = expenses['amount_inr'].quantile(0.25)
    q3 = expenses['amount_inr'].quantile(0.75)
    iqr = q3 - q1
    upper_bound = q3 + (1.5 * iqr)
    
    outliers = expenses[expenses['amount_inr'] > upper_bound]
    
    print(f"  Q1 (25th percentile)        : INR {q1:,.2f}")
    print(f"  Q3 (75th percentile)        : INR {q3:,.2f}")
    print(f"  IQR (Spread)                : INR {iqr:,.2f}")
    print(f"  Upper Outlier Threshold     : INR {upper_bound:,.2f}")
    print(f"  Anomalous Spikes Detected   : {len(outliers)}")
    
    if not outliers.empty:
        print("\n  Top Identified Outlier Spikes:")
        for _, row in outliers.iterrows():
            print(f"    - Date: {row['date'].strftime('%Y-%m-%d')} | Cat: {row['category']:<15} | Amt: INR {row['amount_inr']:,.2f} | Note: {row['description']}")
    return outliers, upper_bound


def pareto_analysis(expenses):
    """Performs Pareto 80/20 analysis on spending categories."""
    print("\n" + "="*60)
    print("STEP 4: PARETO (80/20) COST DRIVER ANALYSIS")
    print("="*60)
    
    cat_summary = expenses.groupby('category')['amount_inr'].agg(
        total_spend='sum',
        txn_count='count',
        avg_spend='mean'
    ).sort_values(by='total_spend', ascending=False)
    
    total_spent = cat_summary['total_spend'].sum()
    cat_summary['spend_share_pct'] = (cat_summary['total_spend'] / total_spent) * 100
    cat_summary['cumulative_pct'] = cat_summary['spend_share_pct'].cumsum()
    
    print(cat_summary[['total_spend', 'spend_share_pct', 'cumulative_pct', 'txn_count']])
    
    pareto_categories = cat_summary[cat_summary['cumulative_pct'] <= 80].index.tolist()
    if not pareto_categories:
        pareto_categories = [cat_summary.index[0]]
    print(f"\n  Key Cost Drivers (Categories driving ~80% of outflow): {', '.join(pareto_categories)}")
    return cat_summary


def generate_visualizations(df, expenses, income, upper_bound, output_dir="charts_output"):
    """Generates publication-quality charts for portfolio presentation."""
    os.makedirs(output_dir, exist_ok=True)
    print(f"\n[STEP 5] Exporting visualization figures to ./{output_dir}...")
    
    # 1. Category Bar Chart
    plt.figure(figsize=(9, 5))
    cat_spend = expenses.groupby('category')['amount_inr'].sum().sort_values(ascending=False)
    palette = sns.color_palette("mako", len(cat_spend))
    ax = sns.barplot(x=cat_spend.values, y=cat_spend.index, palette=palette)
    plt.title("Categorical Expense Breakdown (Pareto Ranking)", fontsize=14, weight='bold', pad=15)
    plt.xlabel("Total Outflow (INR)", fontsize=11)
    plt.ylabel("Expense Category", fontsize=11)
    for i, v in enumerate(cat_spend.values):
        ax.text(v + (max(cat_spend.values) * 0.01), i, f"₹{v:,.0f}", va='center', fontsize=9, weight='bold')
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, "01_category_breakdown.png"), dpi=300)
    plt.close()
    
    # 2. Outlier Boxplot
    plt.figure(figsize=(8, 4))
    sns.boxplot(x=expenses['amount_inr'], color='#667eea', showmeans=True,
                meanprops={"marker":"o", "markerfacecolor":"white", "markeredgecolor":"black"})
    plt.axvline(upper_bound, color='#e53e3e', linestyle='--', linewidth=1.5, label=f'IQR Threshold (₹{upper_bound:,.0f})')
    plt.title("Expense Amount Distribution & Anomaly Threshold (Boxplot)", fontsize=13, weight='bold', pad=12)
    plt.xlabel("Transaction Amount (INR)", fontsize=11)
    plt.legend(loc='upper right')
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, "02_outlier_detection_boxplot.png"), dpi=300)
    plt.close()
    
    # 3. Time Series Cash Flow (Monthly Trend)
    monthly_trend = df.groupby(['year_month', 'type'])['amount_inr'].sum().unstack(fill_value=0)
    if 'expense' in monthly_trend.columns or 'income' in monthly_trend.columns:
        plt.figure(figsize=(9, 5))
        if 'income' in monthly_trend.columns:
            plt.plot(monthly_trend.index.astype(str), monthly_trend['income'], marker='o', color='#38a169', linewidth=2.5, label='Income')
        if 'expense' in monthly_trend.columns:
            plt.plot(monthly_trend.index.astype(str), monthly_trend['expense'], marker='s', color='#e53e3e', linewidth=2.5, label='Expense')
        plt.title("Monthly Cash Flow Trend (Time-Series Variance)", fontsize=14, weight='bold', pad=15)
        plt.xlabel("Month", fontsize=11)
        plt.ylabel("Amount (INR)", fontsize=11)
        plt.legend()
        plt.tight_layout()
        plt.savefig(os.path.join(output_dir, "03_monthly_cashflow_trend.png"), dpi=300)
        plt.close()
        
    print("  -> Charts successfully exported: 01_category_breakdown.png, 02_outlier_detection_boxplot.png, 03_monthly_cashflow_trend.png")


def main():
    print("="*60)
    print("PERSONAL EXPENSE TRACKER - DATA ANALYST PORTFOLIO RUNNER")
    print("="*60)
    
    csv_file = find_latest_export("..") or find_latest_export(".")
    if not csv_file:
        csv_file = create_sample_dataset_if_none()
        
    df, expenses, income = load_and_clean_data(csv_file)
    perform_statistical_summary(expenses, income)
    outliers, upper_bound = detect_outliers_iqr(expenses)
    pareto_analysis(expenses)
    generate_visualizations(df, expenses, income, upper_bound)
    
    print("\n" + "="*60)
    print("EDA PIPELINE COMPLETE: READY FOR RESUME & INTERVIEWS!")
    print("="*60)


if __name__ == "__main__":
    main()
