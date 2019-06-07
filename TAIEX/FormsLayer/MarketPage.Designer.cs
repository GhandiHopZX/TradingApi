namespace FormsLayer
{
    partial class MarketPage
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
            this.Settingsbutton = new System.Windows.Forms.Button();
            this.SuspendLayout();
            // 
            // Settingsbutton
            // 
            this.Settingsbutton.Location = new System.Drawing.Point(614, 430);
            this.Settingsbutton.Name = "Settingsbutton";
            this.Settingsbutton.Size = new System.Drawing.Size(74, 31);
            this.Settingsbutton.TabIndex = 0;
            this.Settingsbutton.Text = "Settings";
            this.Settingsbutton.UseVisualStyleBackColor = true;
            // 
            // MarketPage
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(8F, 16F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(718, 484);
            this.Controls.Add(this.Settingsbutton);
            this.Name = "MarketPage";
            this.Text = "TAPIX";
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Button Settingsbutton;
    }
}

