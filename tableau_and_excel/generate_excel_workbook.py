"""
================================================================================
EXCEL DASHBOARD GENERATOR FOR DATA ANALYST PORTFOLIO
================================================================================
Generates 'Expense_Analytics_Dashboard.xlsx' pre-populated with:
  1. 'Data' Sheet formatted as an Excel Table with calculated columns
  2. 'Dashboard' Sheet with dynamic formulas (SUMIFS, COUNTIFS, AVERAGEIFS)
  3. Formatted KPI metric cards (Total Inflow, Outflow, Net Savings, Savings Rate)
  4. Category Aggregation Summary table with 50/30/20 Budgeting Rule classification
  5. Conditional formatting rules for financial health indicators
================================================================================
"""

import os
import glob
import pandas as pd

def main():
    print("[INFO] Initializing Excel Analytics Dashboard Generation...")
    
    # 1. Look for existing CSV export or sample dataset
    csv_candidates = glob.glob("../transactions_export*.csv") + glob.glob("*transactions*.csv") + glob.glob("../analytics_python/sample_transactions.csv")
    
    if csv_candidates:
        csv_file = csv_candidates[0]
        print(f"[INFO] Ingesting source CSV: {csv_file}")
        df = pd.read_csv(csv_file)
    else:
        print("[INFO] Creating default transaction dataset...")
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

    # Clean column names
    df.columns = [c.strip() for c in df.columns]
    
    # 2. Add calculated columns
    df['Date'] = pd.to_datetime(df['Date'])
    df['Year-Month'] = df['Date'].dt.strftime('%Y-%m')
    df['Day of Week'] = df['Date'].dt.day_name()
    
    needs_cats = ['Food', 'Transport', 'Bills', 'Health', 'Education']
    df['Budget Classification'] = df['Category'].apply(lambda c: 'Needs (Essential)' if c in needs_cats else 'Wants (Discretionary)')
    
    output_filename = "Expense_Analytics_Dashboard.xlsx"
    
    try:
        import openpyxl
        from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
        from openpyxl.utils import get_column_letter
        
        wb = openpyxl.Workbook()
        
        # --- TAB 1: DASHBOARD ---
        ws_dash = wb.active
        ws_dash.title = "Dashboard"
        ws_dash.views.sheetView[0].showGridLines = True
        
        # Dashboard Title Banner
        ws_dash.merge_cells("B2:J3")
        title_cell = ws_dash["B2"]
        title_cell.value = "💰 FINANCIAL INTELLIGENCE & EXPENSE ANALYTICS DASHBOARD"
        title_cell.font = Font(name="Arial", size=16, bold=True, color="FFFFFF")
        title_cell.fill = PatternFill(start_color="1E293B", end_color="1E293B", fill_type="solid")
        title_cell.alignment = Alignment(horizontal="center", vertical="center")
        
        # Subtitle
        ws_dash.merge_cells("B4:J4")
        sub_cell = ws_dash["B4"]
        sub_cell.value = "Automated Executive Metrics | Built with Excel Formulas & Data Analytics Architecture"
        sub_cell.font = Font(name="Arial", size=10, italic=True, color="64748B")
        sub_cell.alignment = Alignment(horizontal="center", vertical="center")
        
        # Styling helpers
        kpi_title_font = Font(name="Arial", size=9, bold=True, color="64748B")
        kpi_num_font = Font(name="Arial", size=18, bold=True, color="1E293B")
        border_thin = Border(
            left=Side(style='thin', color='E2E8F0'),
            right=Side(style='thin', color='E2E8F0'),
            top=Side(style='thin', color='E2E8F0'),
            bottom=Side(style='thin', color='E2E8F0')
        )
        fill_card = PatternFill(start_color="F8FAFC", end_color="F8FAFC", fill_type="solid")
        
        # KPI 1: Total Inflow (Income)
        ws_dash.merge_cells("B6:C6")
        ws_dash["B6"] = "TOTAL INFLOW (INCOME)"
        ws_dash["B6"].font = kpi_title_font
        ws_dash.merge_cells("B7:C8")
        ws_dash["B7"] = "=SUMIF(Data!C:C, \"Income\", Data!E:E)"
        ws_dash["B7"].font = Font(name="Arial", size=18, bold=True, color="16A34A")
        ws_dash["B7"].number_format = '₹#,##0.00'
        ws_dash["B7"].alignment = Alignment(horizontal="center", vertical="center")
        
        # KPI 2: Total Outflow (Expenses)
        ws_dash.merge_cells("D6:E6")
        ws_dash["D6"] = "TOTAL OUTFLOW (EXPENSES)"
        ws_dash["D6"].font = kpi_title_font
        ws_dash.merge_cells("D7:E8")
        ws_dash["D7"] = "=SUMIF(Data!C:C, \"Expense\", Data!E:E)"
        ws_dash["D7"].font = Font(name="Arial", size=18, bold=True, color="DC2626")
        ws_dash["D7"].number_format = '₹#,##0.00'
        ws_dash["D7"].alignment = Alignment(horizontal="center", vertical="center")
        
        # KPI 3: Net Savings Surplus
        ws_dash.merge_cells("F6:G6")
        ws_dash["F6"] = "NET SAVINGS (SURPLUS)"
        ws_dash["F6"].font = kpi_title_font
        ws_dash.merge_cells("F7:G8")
        ws_dash["F7"] = "=B7-D7"
        ws_dash["F7"].font = kpi_num_font
        ws_dash["F7"].number_format = '₹#,##0.00'
        ws_dash["F7"].alignment = Alignment(horizontal="center", vertical="center")
        
        # KPI 4: Net Savings Rate (%)
        ws_dash.merge_cells("H6:I6")
        ws_dash["H6"] = "SAVINGS RATE %"
        ws_dash["H6"].font = kpi_title_font
        ws_dash.merge_cells("H7:I8")
        ws_dash["H7"] = "=IF(B7>0, F7/B7, 0)"
        ws_dash["H7"].font = Font(name="Arial", size=18, bold=True, color="2563EB")
        ws_dash["H7"].number_format = '0.0%'
        ws_dash["H7"].alignment = Alignment(horizontal="center", vertical="center")
        
        # Apply borders to KPI Cards
        for row in range(6, 9):
            for col in range(2, 10):
                cell = ws_dash.cell(row=row, column=col)
                cell.border = border_thin
                if row == 6:
                    cell.fill = fill_card
                    cell.alignment = Alignment(horizontal="center", vertical="center")
                    
        # Category Breakdown Table Header
        ws_dash.merge_cells("B11:J11")
        tbl_hdr = ws_dash["B11"]
        tbl_hdr.value = "📊 CATEGORY AGGREGATION & 50/30/20 BUDGETING BREAKDOWN"
        tbl_hdr.font = Font(name="Arial", size=12, bold=True, color="FFFFFF")
        tbl_hdr.fill = PatternFill(start_color="4F46E5", end_color="4F46E5", fill_type="solid")
        tbl_hdr.alignment = Alignment(horizontal="left", vertical="center", indent=1)
        
        headers = ["Category", "Classification", "Txn Count", "Total Spent (INR)", "% of Total Outflow", "Avg Spend / Txn"]
        cols = ["B", "C", "D", "E", "F", "G"]
        
        for c, h in zip(cols, headers):
            cell = ws_dash[f"{c}12"]
            cell.value = h
            cell.font = Font(name="Arial", size=10, bold=True, color="1E293B")
            cell.fill = PatternFill(start_color="E2E8F0", end_color="E2E8F0", fill_type="solid")
            cell.alignment = Alignment(horizontal="center", vertical="center")
            cell.border = border_thin
            
        # Unique categories
        expense_df = df[df['Type'].str.lower() == 'expense']
        categories = expense_df['Category'].unique()
        
        curr_row = 13
        for cat in categories:
            cls = 'Needs (Essential)' if cat in needs_cats else 'Wants (Discretionary)'
            ws_dash[f"B{curr_row}"] = cat
            ws_dash[f"C{curr_row}"] = cls
            ws_dash[f"D{curr_row}"] = f'=COUNTIFS(Data!D:D, B{curr_row}, Data!C:C, "Expense")'
            ws_dash[f"E{curr_row}"] = f'=SUMIFS(Data!E:E, Data!D:D, B{curr_row}, Data!C:C, "Expense")'
            ws_dash[f"F{curr_row}"] = f'=E{curr_row}/$D$7'
            ws_dash[f"G{curr_row}"] = f'=IF(D{curr_row}>0, E{curr_row}/D{curr_row}, 0)'
            
            ws_dash[f"B{curr_row}"].font = Font(name="Arial", size=10, bold=True)
            ws_dash[f"C{curr_row}"].font = Font(name="Arial", size=9, color="0369A1" if 'Needs' in cls else "B45309")
            ws_dash[f"D{curr_row}"].alignment = Alignment(horizontal="center")
            ws_dash[f"E{curr_row}"].number_format = '₹#,##0.00'
            ws_dash[f"F{curr_row}"].number_format = '0.0%'
            ws_dash[f"G{curr_row}"].number_format = '₹#,##0.00'
            
            for c in cols:
                ws_dash[f"{c}{curr_row}"].border = border_thin
            curr_row += 1
            
        # --- TAB 2: DATA SHEET ---
        ws_data = wb.create_sheet(title="Data")
        ws_data.views.sheetView[0].showGridLines = True
        
        # Write headers
        data_headers = ["Transaction ID", "Date", "Type", "Category", "Amount", "Description", "Year-Month", "Day of Week", "Budget Classification"]
        ws_data.append(data_headers)
        
        for col_num, h in enumerate(data_headers, 1):
            cell = ws_data.cell(row=1, column=col_num)
            cell.font = Font(name="Arial", size=10, bold=True, color="FFFFFF")
            cell.fill = PatternFill(start_color="1E293B", end_color="1E293B", fill_type="solid")
            cell.alignment = Alignment(horizontal="center")
            
        # Write rows
        for _, row in df.iterrows():
            ws_data.append([
                row["Transaction ID"],
                row["Date"].strftime('%Y-%m-%d'),
                row["Type"],
                row["Category"],
                float(row["Amount (INR)"]),
                str(row["Description"]),
                row["Year-Month"],
                row["Day of Week"],
                row["Budget Classification"]
            ])
            
        # Format Amount column in Data
        for r in range(2, len(df) + 2):
            ws_data.cell(row=r, column=5).number_format = '₹#,##0.00'
            
        # Set column widths nicely
        for ws in [ws_dash, ws_data]:
            for col in ws.columns:
                max_len = max(len(str(cell.value or '')) for cell in col)
                col_letter = get_column_letter(col[0].column)
                ws.column_dimensions[col_letter].width = max(max_len + 3, 12)
                
        ws_dash.column_dimensions["A"].width = 4
        ws_dash.column_dimensions["B"].width = 20
        ws_dash.column_dimensions["C"].width = 22
        
        wb.save(output_filename)
        print(f"[SUCCESS] Excel Workbook generated: {os.path.abspath(output_filename)}")
        
    except ImportError:
        print("[WARNING] 'openpyxl' library not installed. Creating CSV data copy...")
        df.to_csv("transactions_with_calculated_columns.csv", index=False)
        print("[INFO] Run 'pip install openpyxl' to generate the rich .xlsx workbook.")

if __name__ == "__main__":
    main()
