namespace TAIEXUI
{
    partial class MarkupForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.ChartHost = new System.Windows.Forms.Integration.ElementHost();
            this.SuspendLayout();
            // 
            // ChartHost
            // 
            this.ChartHost.Dock = System.Windows.Forms.DockStyle.Fill;
            this.ChartHost.Location = new System.Drawing.Point(0, 0);
            this.ChartHost.Name = "ChartHost";
            this.ChartHost.Size = new System.Drawing.Size(1293, 530);
            this.ChartHost.TabIndex = 0;
            this.ChartHost.Text = "Chartup";
            this.ChartHost.Child = null;
            // 
            // MarkupForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(8F, 16F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.AutoSizeMode = System.Windows.Forms.AutoSizeMode.GrowAndShrink;
            this.ClientSize = new System.Drawing.Size(1293, 530);
            this.Controls.Add(this.ChartHost);
            this.Name = "MarkupForm";
            this.Text = "Market";
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Integration.ElementHost ChartHost;
    }
}